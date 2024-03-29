<?php

namespace CodingLiki\PhpMvc\Router;

/**
 * Класс роутера для управления путями
 */
class Router{

    protected $routes = [];
    protected $app;
    protected $uri;
    protected $middlewares = [];
    public $json_array_return = false;
    /**
     * Конструктор принимает и нормализует конфиг
     * также выделяет строку запроса для парсинга от GET параметров
     *
     * @param [type] $app
     * @param array $routes
     */
    function __construct($routes = []){
        $uri = explode("?", $_SERVER['REQUEST_URI'])[0];
        
        if ($uri[strlen($uri)-1] == '/' && $uri != '/') {
            $uri = substr($uri, 0, strlen($uri)-1);
        }

        $two_steps = false; // Флаг вложенности массива путей в routes
        
        /** Сохраняем прослойки */
        if(isset($routes['middlewares'])){
            $this->middlewares = $routes['middlewares'];
            $two_steps = true;     
        }
        
        /** Сохраняем режим отображения результата массива в json */
        if(isset($routes['json_array_return'])){
            $this->json_array_return = $routes['json_array_return'];
            $two_steps = true;     
        }

        if($two_steps){
            $this->routes = $routes['routes'];
        } else {
            $this->routes = $routes;
        }
        // $this->json_array_return = $json_array_return;
        // $this->app = App::$first_app;
        $this->uri = $uri;
        $this->normalizeRoutes();
    }

     /**
      * Нормализует внутренний список путей с упрощённым синтаксисом
      *
      * @return void
      */
    protected function normalizeRoutes(){
        foreach($this->routes as $key => $route){
            $route_new = str_replace('/', '\/', $route[0]);
            $route_new = '/'.$route_new.'$/';
            
            $mathes = [];
            preg_match_all('/[?+](\w+):(\w)/', $route_new, $mathes);
            
            foreach($mathes[0] as $math) {
                $new_math = str_replace('?', '(?<', $math);
                $new_math = str_replace(':', ">\\",  $new_math);
                $new_math .= '+)';
                $route_new = str_replace($math, $new_math, $route_new);
            }
            
            $this->routes[$key][0] = $route_new;
        }
    }

    /**
     * Проверяет все пути с uri
     *
     * @return void
     */
    public function checkRoutes(){
        foreach($this->routes as $route){
            $mathes = [];
            $mathes = $this->routeMatch($route[0], $this->uri);
            if(count($mathes) > 0 ){
                if(isset($route['redirect'])) {
                    $this->uri = $route['redirect'];
                    return $this->checkRoutes();
                }
                unset($mathes[0]);
                $mathes_num = count($mathes) / 2;
                for($i = 1; $i<=$mathes_num; $i++){
                    unset($mathes[$i]);
                }
                $best_math = $route;
                $best_math['params'] = array_values((array)$mathes);
                return $best_math;
            }
        }
    }

    /**
     * Сравниваем нормализованный путь с uri
     *
     * @param [type] $route
     * @param [type] $uri
     * @return void
     */
    protected function routeMatch($route, $uri){
        $mathes = [];
        preg_match($route, $uri, $mathes);
        return $mathes;
    }


    public function processFirstRoute(){
        $first_match = $this->checkRoutes();
        $process_result = [ 'found' => false];

        if(empty($first_match)){
            return $process_result;
        } else {
            $this->checkMiddlewares($first_match);

            if(!isset($first_match['controller'])){
                $mass = explode('@', $first_match[1]);
                $ctrl = $mass[0];
                
                if(isset($mass[1])){
                    $func = $mass[1];
                }
                
                $first_match['controller'] = $ctrl;
                
                if(!isset($first_match['function']) && isset($func)){
                    $first_match['function'] = $func;
                }
            }
            
            $controller = $first_match['controller'];
            $Ctrl = new $controller;
            $result_text = "";
            
            if(isset($first_match['function']) && method_exists($Ctrl, $first_match['function'])){
                $function = $first_match['function'];
                
                if(empty($first_match['params'])){
                    $result_text = $Ctrl->$function();
                } else {
                    $params = $first_match['params'];
                    //print_d(...$params);
                    $result_text = $Ctrl->$function(...$params);
                }
            } else if(!isset($first_match['function'])) {
                $result_text = $Ctrl->index();
            } else {
                //TODO: Добавить Вызов Exception
                // print_r("Method {$first_match['function']} not exists");
            }

            $responce_array_as_json = $this->json_array_return;
            
            if(isset($first_match['json_array_return'])){
                $responce_array_as_json = $responce_array_as_json || $first_match['json_array_return'];
            }
            
            if(!empty($result_text)){
                if(is_array($result_text) && $responce_array_as_json){
                    $process_result['result_text'] = json_encode($result_text); 
                } else{
                    $process_result['result_text'] = $result_text;
                }
            }
            
            $process_result['found'] = true;
            return $process_result;
        }
    }

    public static function processRoutes($routes_mass){
        $found = false;
        foreach($routes_mass as $router){
            /**
             * @var Router $router
             */
            $result = $router->processFirstRoute();
            if($result['found']){
                if(isset($result['result_text'])){
                    echo $result['result_text'];
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            $Ctrl = new \Controllers\Error404;
            $Ctrl->index();
        }
    }
    /**
     * Проверяем и запускаем все прослойки до запуска контроллера
     *
     * @param [type] $first_match
     * @return void
     */
    protected function checkMiddlewares($first_match){
        $middlewares = [];

        // print_r($first_match);
        if(isset($first_match['middlewares'])){
            $middlewares = $first_match['middlewares'];
        } else if(count($first_match) > 2 && isset($first_match[2])){
            $middlewares = $first_match[2];
        }
        /** мы работаем с массивом!!! */
        if(!is_array($middlewares)){
            $middlewares = [$middlewares];
        }

        $middlewares = $middlewares + $this->middlewares;
        

        

        foreach($middlewares as $middleware){
            $m = new $middleware();
            $m->start();
        }
    }
}
