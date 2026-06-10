@extends('layouts.app')
@section('title', 'Settings — Yatri')

@section('content')
<div class="settings-page">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:32px">
    <a href="{{ route('profile', auth()->user()) }}" class="icon-btn">
      <x-icon name="arrow_back" />
    </a>
    <div>
      <h2 style="margin:0">Settings</h2>
      <p class="lead" style="margin:0">Manage your account and preferences.</p>
    </div>
  </div>

  {{-- Profile Photo --}}
  <div class="settings-section">
    <h3><x-icon name="photo_camera" /> Profile Photo</h3>
    <form method="POST" action="{{ route('settings.avatar') }}" enctype="multipart/form-data">
      @csrf
      <div class="avatar-upload">
        <img src="{{ $user->avatar() }}" alt="{{ $user->name }}" id="avatar-preview">
        <div class="avatar-actions">
          <label class="btn btn-outlined btn-sm" style="cursor:pointer">
            <x-icon name="upload" :size="18" /> Choose photo
            <input type="file" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
          </label>
          <button type="submit" class="btn btn-filled btn-sm">Upload</button>
        </div>
      </div>
      @error('avatar')<div class="err">{{ $message }}</div>@enderror
    </form>
  </div>

  {{-- Profile Details --}}
  <div class="settings-section">
    <h3><x-icon name="person" /> Profile Details</h3>
    <form method="POST" action="{{ route('settings.profile') }}">
      @csrf @method('PUT')
      <div class="settings-row">
        <div class="field">
          <label>Display Name</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
          @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" value="{{ $user->email }}" disabled style="opacity:.6">
          <div class="hint">Email cannot be changed.</div>
        </div>
      </div>
      <div class="field">
        <label>Bio</label>
        <textarea name="bio" rows="3" maxlength="500" placeholder="Tell travelers about yourself...">{{ old('bio', $user->bio) }}</textarea>
        @error('bio')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="settings-row">
        <div class="field">
          <label>Current City</label>
          <input type="text" name="current_city" value="{{ old('current_city', $user->current_city) }}" placeholder="e.g. Mumbai, India" data-places>
          @error('current_city')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label>Default Currency</label>
          <select name="default_currency">
            @foreach(['USD'=>'USD — US Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — British Pound','INR'=>'INR — Indian Rupee','JPY'=>'JPY — Japanese Yen','AUD'=>'AUD — Australian Dollar','CAD'=>'CAD — Canadian Dollar','SGD'=>'SGD — Singapore Dollar','AED'=>'AED — UAE Dirham','THB'=>'THB — Thai Baht','IDR'=>'IDR — Indonesian Rupiah','MYR'=>'MYR — Malaysian Ringgit','PHP'=>'PHP — Philippine Peso','VND'=>'VND — Vietnamese Dong','KRW'=>'KRW — Korean Won','CNY'=>'CNY — Chinese Yuan','NZD'=>'NZD — New Zealand Dollar','CHF'=>'CHF — Swiss Franc','SEK'=>'SEK — Swedish Krona','NOK'=>'NOK — Norwegian Krone','TRY'=>'TRY — Turkish Lira','EGP'=>'EGP — Egyptian Pound','ZAR'=>'ZAR — South African Rand','BRL'=>'BRL — Brazilian Real','MXN'=>'MXN — Mexican Peso','RUB'=>'RUB — Russian Ruble','PKR'=>'PKR — Pakistani Rupee','BDT'=>'BDT — Bangladeshi Taka','LKR'=>'LKR — Sri Lankan Rupee','NPR'=>'NPR — Nepalese Rupee'] as $code => $label)
              <option value="{{ $code }}" {{ old('default_currency', $user->default_currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('default_currency')<div class="err">{{ $message }}</div>@enderror
        </div>
      </div>
      <button type="submit" class="btn btn-filled">
        <x-icon name="save" :size="18" /> Save changes
      </button>
    </form>
  </div>

  {{-- Theme --}}
  <div class="settings-section">
    <h3><x-icon name="palette" /> Appearance</h3>
    <p style="color:var(--md-on-surface-variant);font-size:14px;margin:0 0 16px">Choose how Yatri looks on this device.</p>
    <div class="theme-options" id="settings-theme-options">
      <button class="theme-option {{ $user->theme === 'light' ? 'active' : '' }}" onclick="setThemeFromSettings('light')">
        <x-icon name="light_mode" />
        <span>Light</span>
      </button>
      <button class="theme-option {{ $user->theme === 'dark' ? 'active' : '' }}" onclick="setThemeFromSettings('dark')">
        <x-icon name="dark_mode" />
        <span>Dark</span>
      </button>
      <button class="theme-option {{ $user->theme === 'auto' ? 'active' : '' }}" onclick="setThemeFromSettings('auto')">
        <x-icon name="contrast" />
        <span>Auto</span>
      </button>
    </div>
  </div>

  {{-- Change Password --}}
  <div class="settings-section">
    <h3><x-icon name="lock" /> Change Password</h3>
    <form method="POST" action="{{ route('settings.password') }}">
      @csrf @method('PUT')
      <div class="field">
        <label>Current Password</label>
        <div style="position:relative">
          <input type="password" name="current_password" id="current-password" required style="padding-right:44px">
          <button type="button" class="icon-btn" style="position:absolute;right:4px;top:50%;transform:translateY(-50%)" onclick="togglePasswordVisibility('current-password', this)">
            <x-icon name="visibility" :size="20" />
          </button>
        </div>
        @error('current_password')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div class="settings-row">
        <div class="field">
          <label>New Password</label>
          <div style="position:relative">
            <input type="password" name="password" id="new-password" required minlength="8" style="padding-right:44px">
            <button type="button" class="icon-btn" style="position:absolute;right:4px;top:50%;transform:translateY(-50%)" onclick="togglePasswordVisibility('new-password', this)">
              <x-icon name="visibility" :size="20" />
            </button>
          </div>
          @error('password')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label>Confirm New Password</label>
          <input type="password" name="password_confirmation" required>
        </div>
      </div>
      <button type="submit" class="btn btn-filled">
        <x-icon name="lock_reset" :size="18" /> Update password
      </button>
    </form>
  </div>
</div>

@push('scripts')
<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('avatar-preview').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('.icon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    input.type = 'password';
    icon.textContent = 'visibility';
  }
}

function setThemeFromSettings(theme) {
  applyTheme(theme);
  document.querySelectorAll('#settings-theme-options .theme-option').forEach(btn => {
    btn.classList.remove('active');
  });
  event.currentTarget.classList.add('active');
  fetch('{{ route("settings.theme") }}', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ theme: theme })
  });
}
</script>
@endpush
@endsection
