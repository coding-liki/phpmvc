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
    ],
    [
        '^/matches/active',
        'Controllers\MatchesController@activeMatches'
    ],
    [
        '^/matches/search',
        'Controllers\MatchesController@searchMatches'
    ],
    [
        '^/matches/search_get',
        'Controllers\MatchesController@getMatchesSearch'
    ],
    [
        '^/matches/score_chart',
        'Controllers\MatchesController@scoreChart'
    ],
    [
        '^/ajax/matches/active',
        'Controllers\MatchesController@ajaxGetActive',
        'Middlewares\AllowCrossOrigin'
    ],
    [
        '^/the/info/of/php',
        'Controllers\TheInfoController@phpInfo'
    ],
    [
        '^/test/fann',
        'Controllers\TheInfoController@createFannTestFile'
    ]
];