<?php
$output  = [];
$command = './vendor/bin/phpunit';
exec($command, $output);
$ok = (bool)preg_match('/OK \(\d* tests?, \d* assertions?\)/', array_pop($output));
echo (int)(!$ok);
