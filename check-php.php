<?php
header('Content-Type: text/plain');

echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "PDO: " . (extension_loaded('pdo') ? 'AKTIF' : 'TIDAK AKTIF') . PHP_EOL;
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'AKTIF' : 'TIDAK AKTIF') . PHP_EOL;
echo "MySQLi: " . (extension_loaded('mysqli') ? 'AKTIF' : 'TIDAK AKTIF') . PHP_EOL;
