<?php 

/**
 * Необходимо скопировать этот файл в корень сайта
 * 
 */

require "./vendor/CodingLiki/Autoloader/autoloader.php"; // Подключаем автозагрузчик классов

$short_options = "d::t:N:M::";

$long_options = [
    "database::",
    "table:",
    "namespace:",
    "modelname::"
];

$options = getopt($short_options, $long_options);
var_dump($options);