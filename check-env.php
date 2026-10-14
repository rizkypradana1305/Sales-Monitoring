
<?php
header('Content-Type: text/plain');

$vars = [
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLDATABASE',
    'MYSQLUSER',
    'MYSQLPASSWORD'
];

foreach ($vars as $var) {
    $value = getenv($var);

    echo $var . ': ';

    if ($value === false || $value === '') {
        echo "TIDAK TERBACA";
    } elseif ($var === 'MYSQLPASSWORD') {
        echo "TERISI";
    } else {
        echo $value;
    }

    echo PHP_EOL;
}
