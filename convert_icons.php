<?php
/**
 * Convert material-symbols-outlined spans to x-icon components.
 * Line-by-line processing for safety.
 */
$base = '/Users/macraze/Developer/yatri.org';

/* ─── Step 1: CSS ─── */
$css = file_get_contents($base . '/public/css/yatri.css');
$css = preg_replace("/@import\s+url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Material\+Symbols\+Outlined[^']*'\);\s*\n?/", '', $css);
file_put_contents($base . '/public/css/yatri.css', $css);
echo "✅ public/css/yatri.css\n";

/* ─── Step 2: Admin layout ─── */
$admin = file_get_contents($base . '/resources/views/admin/layout.blade.php');
$admin = preg_replace("/<link\s+href=\"https:\/\/fonts\.googleapis\.com\/css2\?family=Material\+Symbols\+Outlined[^\"]*\"\s+rel=\"stylesheet\">\n?/", '', $admin);
$admin = str_replace('.adm-side a .material-symbols-outlined{font-size:20px}', '.adm-side a .icon{font-size:20px}', $admin);
file_put_contents($base . '/resources/views/admin/layout.blade.php', $admin);
echo "✅ admin/layout.blade.php\n";

/* ─── Step 3: Blade files (line-by-line) ─── */
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base . '/resources/views'));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $files[] = $f->getPathname();
    }
}

function convertSpanLine($line) {
    // Skip lines without material-symbols-outlined
    if (strpos($line, 'material-symbols-outlined') === false) return $line;
    
    // Skip lines inside <script> tags for now (handle JS separately)
    // But do convert querySelector references
    
    // Pattern: <span class="material-symbols-outlined">ICON</span>
    // Pattern: <span class="material-symbols-outlined md-NN">ICON</span>  
    // Pattern: <span class="material-symbols-outlined md-NN OTHER">ICON</span>
    // Pattern: <span class="material-symbols-outlined" style="...">ICON</span>
    // Pattern: <span id="..." class="material-symbols-outlined ..." style="...">ICON</span>
    
    // Use a callback to handle all patterns
    $line = preg_replace_callback(
        '/<span\s+([^>]*?)class="material-symbols-outlined([^"]*)"([^>]*)>([^<]+)<\/span>/',
        function($m) {
            $preAttr = trim($m[1]);
            $classRest = trim($m[2]);
            $postAttr = trim($m[3]);
            $iconName = trim($m[4]);
            
            $size = null;
            $filled = false;
            $otherClasses = [];
            $otherAttrs = [];
            
            // Parse class content
            if ($classRest) {
                foreach (explode(' ', $classRest) as $cls) {
                    $cls = trim($cls);
                    if (empty($cls)) continue;
                    if (preg_match('/^md-(\d+)$/', $cls, $sm)) {
                        $size = (int)$sm[1];
                    } elseif ($cls === 'filled') {
                        $filled = true;
                    } elseif (preg_match('/^{{.*}}$/', $cls)) {
                        // Blade expression class - pass through
                        $otherClasses[] = $cls;
                    } elseif (!empty($cls)) {
                        $otherClasses[] = $cls;
                    }
                }
            }
            
            // Parse style
            $otherStyles = [];
            if (preg_match('/style="([^"]*)"/', $postAttr, $sm)) {
                $styleVal = $sm[1];
                foreach (explode(';', $styleVal) as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    if (preg_match('/^font-size\s*:\s*(\d+)px/i', $part, $pm)) {
                        if ($size === null) $size = (int)$pm[1];
                    } elseif (preg_match('/^font-variation-settings\s*:/i', $part)) {
                        if (preg_match("/'FILL'\s*1/i", $part)) $filled = true;
                    } elseif (!preg_match('/^vertical-align\s*:/i', $part)) {
                        $otherStyles[] = $part;
                    }
                }
                // Remove style from postAttr after extracting
                $postAttr = preg_replace('/style="[^"]*"/', '', $postAttr);
            }
            
            // Also check preAttr for style (though unlikely)
            if (preg_match('/style="[^"]*"/', $preAttr, $sm)) {
                $styleVal = $sm[1];
                foreach (explode(';', $styleVal) as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    if (preg_match('/^font-size\s*:\s*(\d+)px/i', $part, $pm)) {
                        if ($size === null) $size = (int)$pm[1];
                    } elseif (preg_match('/^font-variation-settings\s*:/i', $part)) {
                        if (preg_match("/'FILL'\s*1/i", $part)) $filled = true;
                    } elseif (!preg_match('/^vertical-align\s*:/i', $part)) {
                        $otherStyles[] = $part;
                    }
                }
                $preAttr = preg_replace('/style="[^"]*"/', '', $preAttr);
            }
            
            // Collect other attributes (id, etc.) from preAttr
            if ($preAttr) $otherAttrs[] = $preAttr;
            
            // Build x-icon tag
            $attrs = ['name="' . $iconName . '"'];
            if ($size !== null) $attrs[] = ':size="' . $size . '"';
            if ($filled) $attrs[] = ':filled="true"';
            if ($otherAttrs) $attrs[] = implode(' ', $otherAttrs);
            if ($postAttr) $attrs[] = $postAttr;
            if ($otherStyles) $attrs[] = 'style="' . implode(';', $otherStyles) . '"';
            if ($otherClasses) $attrs[] = 'class="' . implode(' ', $otherClasses) . '"';
            
            return '<x-icon ' . implode(' ', $attrs) . ' />';
        },
        $line
    );
    
    return $line;
}

function convertJSLine($line) {
    // Update querySelector('.material-symbols-outlined') → .icon
    $line = preg_replace(
        '/(querySelector(?:All)?\s*\(\s*[\'"])(\.material-symbols-outlined)([\'"]\s*\))/',
        '$1.icon$3',
        $line
    );
    
    // Remove .filled class toggling on icon elements
    // if (icon) icon.classList.add('filled');
    // if (icon) icon.classList.remove('filled');  
    // if (icon) icon.classList.toggle('filled', data.liked);
    $line = preg_replace(
        '/if\s*\(\s*icon\s*\)\s*icon\.classList\.(?:add|remove)\([\'"]filled[\'"]\);\s*/',
        '', $line
    );
    $line = preg_replace(
        '/if\s*\(icon\)\s*icon\.classList\.toggle\([\'"]filled[\'"],\s*data\.liked\);\s*/',
        '', $line
    );
    
    // Remove fontVariationSettings manipulation on likeIcon
    $line = preg_replace(
        '/document\.getElementById\([\'"]likeIcon[\'"]\)\.style\.fontVariationSettings\s*=.*?;\s*/',
        '', $line
    );
    
    // Replace JS innerHTML with icon span for close button
    $line = preg_replace(
        "/btn\.innerHTML\s*=\s*'<span\s+class=\"material-symbols-outlined\"\s+style=\"font-size:16px\">close<\/span>';/",
        "btn.innerHTML = '<svg class=\"icon\" width=\"16\" height=\"16\" viewBox=\"0 -960 960 960\" fill=\"currentColor\"><path d=\"m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z\"/><\/svg>';",
        $line
    );
    
    // Replace JS innerHTML for check + Copied
    $line = preg_replace(
        "/btn\.innerHTML\s*=\s*'<span\s+class=\"material-symbols-outlined\s+md-20\">check<\/span>\s+Copied';/",
        "btn.innerHTML = '<svg class=\"icon\" width=\"20\" height=\"20\" viewBox=\"0 -960 960 960\" fill=\"currentColor\"><path d=\"M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z\"/><\/svg> Copied';",
        $line
    );
    
    return $line;
}

function convertPHPStringLine($line) {
    // Handle icon spans inside PHP string literals in {!! !!}
    // Pattern: '<span class="material-symbols-outlined md-14" style="vertical-align:middle">ICON</span>'
    if (strpos($line, 'material-symbols-outlined') !== false && strpos($line, "span class=\"material-symbols-outlined") !== false) {
        $line = preg_replace_callback(
            "/'<span\s+class=\"material-symbols-outlined\s+md-14\"\s+style=\"vertical-align:middle\">([^<]+)<\/span>'/",
            function($m) {
                return "' . view(\"components.icon\", [\"name\" => \"{$m[1]}\", \"size\" => 14])->render() . '";
            },
            $line
        );
    }
    return $line;
}

foreach ($files as $filepath) {
    $rel = str_replace($base . '/resources/views/', '', $filepath);
    $lines = file($filepath);
    $changed = false;
    $inScript = false;
    
    foreach ($lines as $i => $line) {
        $orig = $line;
        
        if (preg_match('/<script[^>]*>/i', $line)) $inScript = true;
        
        if ($inScript) {
            $line = convertJSLine($line);
        } else {
            $line = convertSpanLine($line);
            $line = convertPHPStringLine($line);
        }
        
        if (preg_match('/<\/script>/i', $line)) $inScript = false;
        
        if ($line !== $orig) {
            $lines[$i] = $line;
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($filepath, implode('', $lines));
        echo "✅ {$rel}\n";
    }
}

/* ─── Step 4: Handle specific complex patterns that the general approach missed ─── */

// planner/show.blade.php: like icon with font-variation-settings containing Blade expression
$path = $base . '/resources/views/planner/show.blade.php';
$c = file_get_contents($path);

$c = preg_replace(
    '/<span\s+id="likeIcon"\s+class="material-symbols-outlined\s+md-20"\s+style="font-variation-settings:\s*{{\s*\$trip->isLikedBy\(auth\(\)->user\(\)\)\s*\?\s*"[^"]*"\s*:\s*"[^"]*"\s*}};vertical-align:middle">favorite<\/span>/',
    '<x-icon name="favorite" :size="20" id="likeIcon" :filled="$trip->isLikedBy(auth()->user())" />',
    $c
);

// planner/show.blade.php: culture do/dont icons  
// <span class="material-symbols-outlined">check_circle</span> Do  
// <span class="material-symbols-outlined">cancel</span> Don't
// These are already handled by the general pattern but let me verify

// planner/show.blade.php: comments icon (chat_bubble) 
// <span class="material-symbols-outlined md-16" style="vertical-align:middle">chat_bubble</span>
// This should be handled already

file_put_contents($path, $c);
echo "✅ planner/show.blade.php (like icon)\n";

// Check for any remaining references
echo "\n─── Remaining material-symbols-outlined references ───\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base . '/resources/views'));
$leftovers = [];
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $content = file_get_contents($f->getPathname());
        $lines = explode("\n", $content);
        foreach ($lines as $ln => $line) {
            if (strpos($line, 'material-symbols-outlined') !== false) {
                $rel = str_replace($base . '/resources/views/', '', $f->getPathname());
                $leftovers[] = "{$rel}:".($ln+1).": ".trim(substr($line, 0, 200));
            }
        }
    }
}
if (empty($leftovers)) {
    echo "None! All clean.\n";
} else {
    foreach ($leftovers as $lf) {
        echo "  {$lf}\n";
    }
}

echo "\nDone!\n";
