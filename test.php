<?php
header("Content-Type: text/plain");
echo "URL: http://10.0.0.28:8090\n";
$mysqli = new mysqli("wp_mariadb", "root", "root", "wordpress");
echo "DB: " . ($mysqli ? "OK" : "FAIL") . "\n";

