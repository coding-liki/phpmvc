<?php 

/**
 * Необходимо скопировать этот файл в корень сайта
 * 
 */

require "./vendor/CodingLiki/Autoloader/autoloader.php"; // Подключаем автозагрузчик классов

use CodingLiki\PhpMvc\Models\ModelCreator;

function checkAllInArray($strings, $arr){
    foreach($strings as $str){
        if(!in_array($str, $arr)){
            return false;
        }
    }

    return true;
}

function checkOneInArray($strings, $arr){
    foreach($strings as $str){
        if(in_array($str, $arr)){
            return true;
        }
    }

    return false;
}

$short_options = "d::t:N:M::";

$long_options = [
    "database::",
    "table:",
    "namespace:",
    "modelname::"
];

$required_t = [
    't',
    'table'
];

$required_n= [
    'N',
    'namespace'
];

$options = getopt($short_options, $long_options);
var_dump($options);

if(!checkOneInArray($required_n, $options) || !checkOneInArray($required_t, $options)){
    echo "Необходимо указать имя таблицы и пространство имён для будующей модели!!!\n";
    exit(1);
}


$namespace = $options['N'] ?? $options['namespace'];
$table_name = $options['t'] ?? $options['table'];
$model_name = $options['M'] ?? $options['modelname'] ?? "";
$db_name = $options['d'] ?? $options['database'] ?? "";


$model_creator = new ModelCreator();

$model_creator->setModelName($model_name);
$model_creator->setDbName($db_name);

$model_creator->setTableName($table_name);
$model_creator->setModelNamespace($namespace);

$model_creator->getTableSchema();