<?php
/**
 * Файл с настройками путей
 */


/**
 * Список существующих путей
 * 
 * вид
 * [
 *  <path> : regexp выражение для определения пути
 *  <controller@method> : путь до контроллера и название метода
 *  <middleware>  : путь до прослойки, либо массив путей
 * ]
 */
$routes = [
    [
        '/',
        'Controllers\IndexController'
    ]
];