<?php
// Debug transient
header('Content-Type: text/plain');
$trans = get_site_transient('update_core');
print_r($trans);
exit;