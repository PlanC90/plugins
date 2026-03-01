<?php
$gateway_file = 'includes/class-wc-gateway-omnixep.php';
if (file_exists($gateway_file)) {
    echo md5_file($gateway_file);
} else {
    echo "FILE NOT FOUND";
}
