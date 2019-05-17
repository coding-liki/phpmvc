<?php

namespace CodingLiki\PhpMvc\App;

class App{
    public static $app_instance = null;

    public static function getInstance(){
        if(static::$app_instance == null){
            static::$app_instance = new App();
        }
    }
}