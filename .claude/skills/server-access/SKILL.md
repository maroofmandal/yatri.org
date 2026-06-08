---
name: server-access
description: SSH access and live-ops steps for the yatri.org production server (Oracle Ubuntu, Laravel app under user `yatri`). Use whenever you need to log into the yatri.org server to inspect or edit live app code, run artisan/tinker, query the database, manage users, or read logs. Trigger with /server-access or any request to "log into the yatri server".
---

# yatri.org — Server Access

Production host for yatri.org. Use when a fix must be applied or diagnosed on the live server, not just the repo.

## Login

```bash
ssh -i ~/.ssh/sattaz-key ubuntu@155.248.246.43
sudo su - yatri
cd ~/htdocs/yatri.org
```

Non-interactive (one-shot commands for tooling):

```bash
ssh -i ~/.ssh/sattaz-key ubuntu@155.248.246.43 "sudo su - yatri -c 'cd ~/htdocs/yatri.org && <cmd>'"
```

- SSH user `ubuntu` has passwordless `sudo`. App files owned by user `yatri` — run app/file commands as `yatri`.
- App root: `/home/yatri/htdocs/yatri.org`. Laravel 12, PHP CLI available as `php`.

## Running PHP / artisan on prod

`php artisan tinker --execute` and `tinker < file` are unreliable over nested ssh+sudo (terminal type "unknown", backslash mangling of namespaces, parse errors). **Don't fight the quoting.** Instead bootstrap Laravel from a standalone script shipped as base64:

```bash
read -r -d '' SCRIPT <<'PHPEOF'
<?php
require '/home/yatri/htdocs/yatri.org/vendor/autoload.php';
$app = require '/home/yatri/htdocs/yatri.org/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// ... your code, full Laravel app booted ...
PHPEOF
B64=$(printf '%s' "$SCRIPT" | base64)
ssh -i ~/.ssh/sattaz-key ubuntu@155.248.246.43 "sudo su - yatri -c 'echo $B64 | base64 -d > /tmp/x.php && php /tmp/x.php; rm -f /tmp/x.php'"
```

- If the script interpolates a local shell var (e.g. a password), use an **unquoted** heredoc (`<<PHPEOF`) and escape PHP `$` as `\$`. For pure PHP with no interpolation, use a **quoted** heredoc (`<<'PHPEOF'`) and leave `$` bare.

## Users / admin (gotcha)

`App\Models\User` is **fully guarded** on prod — mass assignment throws `Add [email] to fillable...`. Do NOT use `create()` / `updateOrCreate()` / `firstOrNew([...])` with attribute arrays. Set properties one by one:

```php
$u = \App\Models\User::where('email','admin@yatri.org')->first() ?: new \App\Models\User();
$u->name = 'Admin';
$u->email = 'admin@yatri.org';
$u->role = 'admin';                 // admin gate is: role === 'admin'
$u->password = \Illuminate\Support\Facades\Hash::make($pw);
$u->email_verified_at = now();
$u->save();
```

Admin gate: `User::isAdmin()` returns `role === 'admin'`. Admin panel under `/admin`, guarded by `EnsureAdmin` middleware.

## Notes

- Local users are NOT on prod — prod DB is separate. A fresh deploy can have zero users; create the admin directly on the server (above).
- `GEMINI_API_KEY` for live AI is stored in **DB settings** (`Setting::get('gemini_api_key')`), which overrides the `.env` value.
