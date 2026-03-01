<?php
$gateway_file = 'includes/class-wc-gateway-omnixep.php';
if (file_exists($gateway_file)) {
    echo "MD5: " . md5_file($gateway_file) . "\n";
} else {
    echo "File not found at: " . realpath($gateway_file) . "\n";
}
unlink(__FILE__);
