<?php

namespace CodingLiki\PhpMvc\Models;

use CodingLiki\Db\DbFactory;
use CodingLiki\Db\QueryBuilder;


class ModelCreator{
    public $db = null ;
    protected $db_name = "main"; 
    protected $table_name = "";
    protected $model_name = "";


    public function setDbName($db_name){
        $this->db_name = $db_name;
    }

    public function setModelName($model_name){
        $this->model_name = $model_name;
    }

    public function initDb(){
        $db_config = "db.".$this->db_name;

        $db = DbFactory::getDbObject($db_config);

        $this->db = $db;
    }

    public function setTableName($table_name){
        $this->table_name = $table_name;
    }

    public function getTableSchema(){

        if($this->db == null){
            $this->initDb();
        }
        $query_builder = new QueryBuilder();

        $query_builder->buildSelect("INFORMATION_SCHEMA.COLUMNS", "COLUMN_NAME, data_type");
        $query_builder->addWhere(['TABLE_NAME']);

        $values = ['TABLE_NAME' =>  $this->table_name];

        $query = $query_builder->getQuery();

        $result = $this->db->mainQuery($query, $values);
        
        $model_template = file_get_contents(__DIR__."/model_template.tpl");
        
        $model_template = str_replace('{{table}}', $this->table_name, $model_template);
        $model_template = str_replace('{{db_name}}', $this->db_name, $model_template);
        
        $model_name = str_replace("_", "",ucwords($this->table_name, "_"));
        if($this->model_name != ""){
            $model_name = $this->model_name;
        }

        $model_template = str_replace('{{name}}', $model_name, $model_template);

        $fields = "";
        $scheme = "";
        foreach($result as $field){
            $name = $field['column_name'];
            $type = $field['data_type'];
            $fields .= "$name: тип - $type\n * ";
            $scheme .= "    '$name' => ['type' => '$type'],\n    ";
        }

        $model_template = str_replace('{{fields}}', $fields, $model_template);
        $model_template = str_replace('{{table_scheme}}', $scheme, $model_template);
        $filename = __DIR__."/$model_name.php";
        file_put_contents($filename, $model_template);
        echo $model_template;

        echo "Модель для таблицы \n\t`$this->table_name`\nСгенерирована в \n\t`$filename`\n";
        // print_r($result);
    }
}
