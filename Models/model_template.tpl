<?php

namespace {{namespace}};

use CodingLiki\PhpMvc\Models\Model;

/**
 * Модель для работы с таблицей {{table}}
 * 
 * Поля таблицы:
 * {{fields}}
 */
class {{name}} extends Model{
    protected $table_name = "{{table}}";
    protected static $db_name = "{{db_name}}";

    protected $table_scheme = [
    {{table_scheme}}
    ];
}
