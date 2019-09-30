<?php

$path = __DIR__."/../../../../index.php";

$str = <<<PHP
<?php
use CodingLiki\PhpMvc\App\App;

require_once "./vendor/CodingLiki/Autoloader/autoloader.php";
require_once "./vendor/CodingLiki/View/include/helpers.php";

\$app = App::getInstance();

\$app->start("app_middlewares", [ "routes.routes" ]);

PHP;

file_put_contents($path, $str);
