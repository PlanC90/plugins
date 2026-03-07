<?php
$file = 'c:\Users\ceyhun\Local Sites\xepmarket\app\public\wp-content\plugins\omnixep-woocommerce\omnixep-woocommerce.php';
$content = file_get_contents($file);

$replacements = [
    'ÄŸÅ¸â€œâ€ž' => '📄',
    'Ã¢Â Å’' => '❌',
    'ÄŸÅ¸â€ â€ž' => '🔄',
    'Ã¢Â' => '',
    'ℹ️ Â' => 'ℹ️',
];

foreach ($replacements as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        echo "Replaced '$search' with '$replace' ($count times)\n";
    }
}

file_put_contents($file, $content);
echo "Finished fixing $file\n";
