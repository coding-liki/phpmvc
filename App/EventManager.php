<?php

namespace CodingLiki\PhpMvc\App;

class EventManager{
    public static $events;

    public static function triggerEvent($name, ...$vars){
        $events = static::$events;

        if(!isset($events[$name])){
            return;
        }

        foreach($events[$name] as $event){
            $class = $event['class'];
            $function = $event['function'];
            $additional_args = $event['args'] ?? null;
            if($additional_args != null){
                $class::$function(...$additional_args, ...$vars);
            } else {
                $class::$function(...$vars);
            }
        }
    }

    public static function bindEvent($name, $class, $function, $args = null){
        $event = [
            'class' => $class,
            'function' => $function
        ];

        if($args != null){
            $event['args'] = $args;
        }

        if(isset(static::$events[$name])){
            static::$events[$name] = [];
        }

        static::$events[$name][] = $event;
    }
}