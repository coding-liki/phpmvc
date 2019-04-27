<?php

namespace CodingLiki\PhpMvc\View;

use CodingLiki\Configs\Config;

class View{
    public static function view($view_name, $values = []){
        $views_folder = Config::config("main.views_folder") ?? "Views";

        $views_file = $views_folder."/$view_name.php";

        // echo $views_file;

        if(file_exists($views_file)){
            // echo "Можно подключать";
            foreach($values as $key => $value){
                $$key = $value;
            }

            include $views_file;
        }
    }
}
