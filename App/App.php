<?php

namespace CodingLiki\PhpMvc\App;

use CodingLiki\Configs\Config;
use CodingLiki\PhpMvc\App\Middlewares\Middleware;
use CodingLiki\PhpMvc\Router\Router;

class App{
    /**
     * Singleton приложения
     *
     * @var App
     */
    public static $app_instance = null;

    /**
     * Получаем экземпляр приложения
     *
     * @return App
     */
    public static function getInstance(){
        if(static::$app_instance == null){
            static::$app_instance = new App();
        }

        return static::$app_instance;
    }

    public function start( $middlewares_config = null, $routes_config = "routes.routes"){
        $this->loadMiddlewares($middlewares_config);
        
        if(!is_array($routes_config)){
            $routes_config = [$routes_config];
        }
        $routes_mass = [];
        foreach ($routes_config as $config_file) {
            $routes_mass[] = new Router(Config::config($config_file));
        }

        EventManager::triggerEvent("App.start");
        Router::processRoutes($routes_mass);
        EventManager::triggerEvent("App.end");
    }

    /**
     * Подгружаем и запускаем все прослойки из переданного конфига
     *
     * @param [type] $middlewares_config
     * @return void
     */
    protected function loadMiddlewares($middlewares_config){
        if($middlewares_config == null){
            return;
        }

        $middlewares = Config::config($middlewares_config);
        foreach($middlewares as $middleware){    
            $class= $middleware['class'];

            /**
             * класс должен быть унаследован от Middleware
             * @var Middleware $middleware_object
             */
            $middleware_object = new $class();
            $middleware_object->start();
        }
    }
}