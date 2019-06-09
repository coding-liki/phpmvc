<?php

namespace CodingLiki\PhpMvc\Models;

use CodingLiki\Configs\Config;
use CodingLiki\Db\DbFactory;
use CodingLiki\Db\QueryBuilder;

class Model implements \ArrayAccess{
    public $db = null;
    protected static $db_name = "main"; 
    protected static $model_instance = null;
    
    protected $table_name = "";
    protected $table_fields = [];
    protected $table_old_fields = [];
    protected $order_by = [];

    public $table_index = "id";
    public $types_unmanaged = false;
    public function offsetExists($name) {
        return array_key_exists($name, $this->table_fields);
    }

    public function offsetGet($name) {
        if(array_key_exists($name, $this->table_fields)){
            return $this->table_fields[$name];
        } else {
            return null;
        }
    }

    public function offsetSet($name , $value) {
        if (array_key_exists($name, $this->table_fields)) {
            $this->table_fields[$name] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->$offset);
    }

    public function __construct($db_config)
    {
        // echo "before getDB\nwith config($db_config)\n";
        $db = DbFactory::getDbObject($db_config);
        // print_r($db);
        // echo "class is ".get_class($this)."\n";
        if ($db) {
            $this->db = $db;
        } else {
        }
    }

    public function orderBy($fields){
        $this->order_by = $fields;
    }
    public static function getInstance(){
        
        $model_instance = new static("db.".static::$db_name);

        return $model_instance;
    } 

    public static function find($index, $value = null){
        $model = static::getInstance();
        if($value == null){
            $value = $index;
            $index = $model->table_index;
        }

        $query_builder = new QueryBuilder();
        $query_builder->buildSelect($model->table_name);
        $query_builder->addWhere([$index => $value]);
        $query = $query_builder->getQuery();
        
        $result = $model->db->mainQuery($query, [$index => $value])[0];

        if(!$result){
            return false;
        }

        $result = $model->unmanageTypes($result);

        $model->setFields($result);

        return $model;
    }
    public function setFields($fields){
        $this->table_fields = $fields;
        $this->table_old_fields = $fields;
    }

    public function getFields(){
        return $this->table_fields;
    }
    public static function create($values) {
        $model = self::getInstance();
        $query_builder = new QueryBuilder();

        $query_builder->buildInsert($model->table_name);

        $query_builder->addInsertFields(array_keys($values));
        $query = $query_builder->getQuery();
        $result = $model->db->mainQuery($query, $values);
        
        $last_id = $model->db->getLastInsertId($model->table_name, $model->table_index);

        $model = static::find($last_id);
        
        return $model;
    }

    public function __get($name) 
    {
        if(array_key_exists($name, $this->table_fields)){
            return $this->table_fields[$name];
        } else {
            return null;
        }
    }

    public function __set($name, $value) 
    {
        if (array_key_exists($name, $this->table_fields)) {
            $this->table_fields[$name] = $value;
        }
    }
    public static function count($where = [], $fields = "*"){
        $model = self::getInstance();
        $query_builder = new QueryBuilder();

        $query_builder->buildCount($model->table_name, $fields);
        $query_builder->addWhere($where);

        $query = $query_builder->getQuery();

        $result = $model->db->mainQuery($query, $where);

        return $result[0]['count'];
    }

    public function save(){
        $update_values = [];

        $fields = $this->manageTypes($this->table_fields);
        $old_fields = $this->manageTypes($this->table_old_fields);
        foreach ($fields as $key => $value) {
            # code...
            if($value != $old_fields[$key]){
                $update_values[$key] = $value;
            }
        }

        if(count($update_values) == 0){
            return $this;
        }

        $index = $this->table_index;
        $index_val = $this->table_old_fields[$index];
        $query_builder = new QueryBuilder();

        $query_builder->buildUpdate($this->table_name);
        $query_builder->addUpdateFields(array_keys($update_values));
        $query_builder->addWhere([ $index => $index_val]);
        $query = $query_builder->getQuery();


        $update_values[$index] = $index_val;
        $result = $this->db->mainQuery($query, $update_values);

        foreach ($this->table_fields as $key => $value) {
            if($fields[$key] != $old_fields[$key]){
                $this->table_old_fields[$key] = $value;
            }
        }
        return $this;
    }
    public function manageTypes($fields){
        foreach($fields as $key => $field){
            if(isset($this->table_scheme) && isset($this->table_scheme[$key])){
                if(!is_array($this->table_scheme[$key])){
                    $this->table_scheme[$key] = [$this->table_scheme[$key]];
                }

                foreach ($this->table_scheme[$key] as $type) {
                    switch ($type) {
                        case 'json':
                            $fields[$key] = json_encode($fields[$key], true);
                            break;
                    }
                }
            }
        }

        return $fields;
    }

    public function unmanageTypes($fields){
        foreach($fields as $key => $field){
            if(isset($this->table_scheme) && isset($this->table_scheme[$key])){
                if(!is_array($this->table_scheme[$key])){
                    $this->table_scheme[$key] = [$this->table_scheme[$key]];
                }

                foreach ($this->table_scheme[$key] as $type) {
                    switch ($type) {
                        case 'json':
                            $fields[$key] = json_decode($fields[$key], true);
                            break;
                    }
                }
            }
        }

        return $fields;
    }
    public static function where($values, $order_by = []){
        $model = self::getInstance();
        $query_builder = new QueryBuilder();

        $query_builder->buildSelect($model->table_name);
        $query_builder->addWhere($values);
        $query_builder->orderBy($order_by);

        $query = $query_builder->getQuery();

        $result = $model->db->mainQuery($query, $values);
        $model_array = [];
        if(!is_array($result)){
            $result = [];
        }
        foreach( $result as $row){
            $model_instance = static::getInstance();

            $row = $model_instance->unmanageTypes($row);
            $model_instance->setFields($row);
            $model_array[] = $model_instance;
        }
        return $model_array;
    }

}
