<?php

namespace CodingLiki\PhpMvc\Models;

use CodingLiki\Configs\Config;
use CodingLiki\Db\DbFactory;
use CodingLiki\Db\QueryBuilder;

class Model implements \ArrayAccess{
    public $db = null;
    protected static $db_name = "main"; 
    protected static $model_instance = null;
    protected static $limit = null;
    protected $table_name = "";
    protected $table_fields = [];
    protected $table_old_fields = [];
    protected $order_by = [];
    protected $times = false;
    protected $updated_at_field = "updated_at";
    protected $created_at_field = "created_at";
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
        $db = DbFactory::getDbObject($db_config);
        if ($db) {
            $this->db = $db;
        } else {
        }
    }
    public static function all(){
        return self::where([]);
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
        
        $result = $model->db->mainQuery($query->last_query, [$index => $value])[0];

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

        if($model->times){
            $values[$model->created_at_field] = time();
        }
        $query_builder->addInsertFields(array_keys($values));
        $query = $query_builder->getQuery();
        $result = $model->db->mainQuery($query->last_query, $values);
        
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

        $result = $model->db->mainQuery($query->last_query, $where);

        return $result[0]['count'];
    }

    public function save(){
        $update_values = [];

        $fields = $this->manageTypes($this->table_fields);
        $old_fields = $this->manageTypes($this->table_old_fields);
        foreach ($fields as $key => $value) {
            if($value != $old_fields[$key]){
                $update_values[$key] = $value;
            }
        }

        if(count($update_values) == 0){
            return $this;
        }

        if($this->times){
            $update_values[$this->updated_at_field] = time();
        }
        $index = $this->table_index;
        $index_val = $this->table_old_fields[$index];
        $query_builder = new QueryBuilder();

        $query_builder->buildUpdate($this->table_name);
        $query_builder->addUpdateFields(array_keys($update_values));
        $query_builder->addWhere([ $index => $index_val]);
        $query = $query_builder->getQuery();


        $update_values[$index] = $index_val;
        $result = $this->db->mainQuery($query->last_query, $update_values);

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
                        case 'bool':
                        case 'boolean':
                            $fields[$key] = ($fields[$key] === 'f' || $fields[$key] === 0 || $fields[$key] === false ) ? 0 : 1;
                            break;
                        case 'json':
                        case 'jsonb':
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
                        case 'bool':
                        case 'boolean':
                            $fields[$key] = ($fields[$key] === 'f' || $fields[$key] === 0 || $fields[$key] === false ) ? false : true;
                            break;
                        case 'json':
                        case 'jsonb':
                            $fields[$key] = json_decode($fields[$key], true);
                            break;
                    }
                }
            }
        }

        return $fields;
    }
    public static function limit($limit){
        self::$limit = intval($limit);
    }
    public static function where($values, $order_by = [], $fields = "*", $return_query = false){
        
        $model = self::getInstance();
        $query_builder = new QueryBuilder();

        $query_builder->buildSelect($model->table_name, $fields);
        $query_builder->addWhere($values);
        $query_builder->orderBy($order_by);

        if(self::$limit){
            $query_builder->addLimit(self::$limit);
            self::$limit = null;
        }

        $query = $query_builder->getQuery();
        if(!empty($query->additional_values)){
            $values =  $query->additional_values +$values;
        }
        if($return_query){
            return $query;
        }
        
        $result = $model->db->mainQuery($query->last_query, $values);
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
