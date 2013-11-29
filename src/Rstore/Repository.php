<?php

namespace Rstore;

use Predis\Client,
    stdClass;

class Repository {

    public $connection = null;
    protected $models = array();

    public function __construct(Client $connection, $models) {
        $this->connection = $connection;
        $this->models = $models;
    }

    public function create($modelName, $properties = array()) {
        $model = null;
        if(isset($this->models[$modelName])) {
            $model = new stdClass();
            foreach($this->models[$modelName]['properties'] as $propertyName => $property) {
                if(isset($property['default'])) {
                    $model->$propertyName = $property['default'];
                    continue;
                }
            }
            $model->created_date = null;
            $model->modified_date = null;
            $model->id = 0;
            $model->name = $modelName;
            foreach($properties as $property => $value) {
                $model->$property = $value;
            }
        } else {
            throw new Exception\ModelNotFound($modelName.' model not found');
        }
        return $this->fillModelProperties($model);
    }

    public function save(stdClass $model) {
        $this->validate($model);
        if(!$model->created_date) {
            $model->created_date = time();
        }
        $model->modified_date = time();
        if(!$model->id) {
            $model->id = $this->getNewID($model->name);
        }
        $this->connection->rpush($model->name, $model->id);
        foreach($model as $property => $value) {
            if(is_object($value)) {
                $this->save($value);
                $this->connection->hset($model->name.':'.$model->id, $property, $value->name.':model:'.$value->id);
            } else if(is_array($value)) {
                foreach($value as $v) {
                    if(is_object($v)) {
                        $this->save($v);
                        $this->connection->rpush($model->id.':list:'.$property, $v->name.':model:'.$v->id);
                    } else {
                        $this->connection->rpush($model->id.':list:'.$property, $v);
                    }
                    $this->connection->hset($model->name.':'.$model->id, $property, $model->id.':list:'.$property);
                }
            } else {
                $this->connection->hset($model->name.':'.$model->id, $property, $value);
            }
        }
        foreach($this->models[$model->name]['properties'] as $propertyName => $property) {
            if(isset($property['index']) && $property['index']) {
                $this->connection->hset($model->name.':'.$propertyName, $model->$propertyName, $model->id);
            }
        }
    }

    public function loadByIndex($modelName, $index, $value) {
        $id = $this->connection->hget($modelName.':'.$index, $value);
        $result = null;
        if($id) {
            $result = new stdClass();
            foreach($this->connection->hgetall($modelName.':'.$id) as $property => $value) {
                if(strpos($value, ':model:') !== false) {
                    $value = $this->getModelFromIdentifier($value);
                } else if(strpos($value, ':list:') !== false) {
                    $value = $this->getArrayFromIdentifier($value);
                }
                $result->$property = self::getValueType($value);
            }
            $result = $this->fillModelProperties($result);
        }
        return $result;
    }

    public function load($modelName, $start, $limit) {
        $results = array();
        foreach($this->connection->lrange($modelName, $start, $limit) as $modelID) {
            $model = new stdClass();
            foreach($this->connection->hgetall($modelName.':'.$modelID) as $property => $value) {
                if(strpos($value, ':model:') !== false) {
                    $model->$property = $this->getModelFromIdentifier($value);
                } else if(strpos($value, ':list:') !== false) {
                    $model->$property = $this->getArrayFromIdentifier($value);
                } else {
                    $model->$property = self::getValueType($value);
                }
            }
            $results[] = $this->fillModelProperties($model);
        }
        return $results;
    }

    public function validate(stdClass $model) {
        foreach($this->models[$model->name]['properties'] as $propertyName => $property) {
            $this->validateType($propertyName, $property, $model);
        }
    }

    protected function validateType($propertyName, $propertyDef, $model) {
        $value = $model->$propertyName;
        if($propertyName != 'id' && isset($propertyDef['required']) && $propertyDef['required'] && !$value) {
            throw new Exception\Validation($propertyName." is required");
        }
        switch($propertyDef['type']) {
            case 'string':
                if(is_string($value)) {
                    if(isset($propertyDef['maxlength']) && strlen($value) > $propertyDef['maxlength']) {
                        throw new Exception\Validation($propertyName." exceeds max length");
                    }
                    return true;
                }
                throw new Exception\Validation($propertyName." should be string, is not");
            case 'integer':
                if(!is_numeric($value)) {
                    throw new Exception\Validation($propertyName." should be integer, is not");
                }
                return true;
            case 'model':
                if($value && (!is_object($value) || $value->name != $propertyDef['ref'])) {
                    throw new Exception\Validation($propertyName." should be model, is not");
                }
                return true;
            case 'array':
                if(isset($propertyDef['ref'])) {
                    if(is_array($value)) {
                        foreach($value as $child) {
                            if(is_object($child) && $child->name == $propertyDef['ref']) {
                                continue;
                            }
                            throw new Exception\Validation($propertyName." should contain only models, does not");
                        }
                        return true;
                    }
                    throw new Exception\Validation($propertyName." should be an array, is not");
                }
                return is_array($value);
            default:
                throw new Exception\Validation($propertyName." is an invalid type");
        }
    }

    protected function getModelFromIdentifier($identifier) {
        $parts = explode(':', $identifier);
        if(sizeof($parts) == 3) {
            return $this->loadByIndex($parts[0], 'id', $parts[2]);
        }
        throw new Exception\InvalidIdentifier();
    }

    protected function getArrayFromIdentifier($identifier) {
        $parts = explode(':', $identifier);
        $results = array();
        if(sizeof($parts) == 3) {
            $values = $this->connection->lrange($identifier, 0, -1);
            foreach($values as $value) {
                if(strpos($value, ':model:') !== false) {
                    $results[] = $this->getModelFromIdentifier($value);
                } else {
                    $results[] = $value;
                }
            }
        }
        return $results;
    }

    protected function getNewID($modelName) {
        return $this->connection->hincrby('auto_increment', $modelName, '1');
    }

    private function fillModelProperties(stdClass $model) {
        foreach($this->models[$model->name]['properties'] as $property => $value) {
            if(!isset($model->$property)) {
                $type = $this->models[$model->name]['properties'][$property]['type'];
                switch($type) {
                    case 'integer':
                        $model->$property = 0;
                        break;
                    case 'string':
                        $model->$property = "";
                        break;
                    case 'array':
                        $model->$property = array();
                        break;
                    default:
                        $model->$property = null;
                }
            }
        }
        return $model;
    }

    private static function getValueType($value) {
        if(is_numeric($value)) {
            return (int)$value;
        } else {
            return $value;
        }
    }
}
