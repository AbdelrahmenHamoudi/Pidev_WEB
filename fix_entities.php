<?php
$files = ['src/Entity/Trajet.php', 'src/Entity/Voiture.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    // Replace: private type $name; -> private ?type $name = null;
    $content = preg_replace('/private\s+(int|string|float|bool|\\\\?DateTimeInterface|Voiture)\s+\\$([a-zA-Z0-9_]+)\s*;/m', 'private ?$1 $$2 = null;', $content);
    
    file_put_contents($file, $content);
}
echo "Entities fixed.\n";
