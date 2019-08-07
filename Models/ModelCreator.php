<?php

namespace CodingLiki\PhpMvc\Models;

use CodingLiki\Db\DbFactory;
use CodingLiki\Db\QueryBuilder;


class ModelCreator{
    public $db = null ;
    protected $db_name = "main"; 
    protected $table_name = "";
    protected $model_name = "";
    protected $model_namespace = "";

    public function setDbName($db_name){
        if ($db_name != "") {
            $this->db_name = $db_name;
        } else {
            $this->db_name = "main";
        }
    }

    public function setModelName($model_name){
        $this->model_name = $model_name;
    }

    public function setModelNamespace($model_namespace){
        $this->model_namespace = $model_namespace;
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
        

        $values = ['TABLE_NAME' =>  $this->table_name];
        
        $query_builder->addWhere($values);
        
        $query = $query_builder->getQuery();

        $result = $this->db->mainQuery($query->last_query, $values);
        
        $model_template = file_get_contents(__DIR__."/model_template.tpl");
        
        $model_template = str_replace('{{table}}', $this->table_name, $model_template);
        $model_template = str_replace('{{db_name}}', $this->db_name, $model_template);
        $model_template = str_replace('{{namespace}}', $this->model_namespace, $model_template);
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
        $model_folder = "./".str_replace('\\', DIRECTORY_SEPARATOR, $this->model_namespace);
        $filename = $model_folder."/$model_name.php";
        file_put_contents($filename, $model_template);
        // echo $model_template;

        echo "Модель для таблицы \n\t`$this->table_name`\nСгенерирована в \n\t`$filename`\n";
        // print_r($result);
    }
}
