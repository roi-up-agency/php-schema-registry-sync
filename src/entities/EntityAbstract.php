<?php
namespace SchemaRegistrySync\Entities;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use mysql_xdevapi\Exception;
use Psr\Http\Message\ResponseInterface;
use SchemaRegistrySync\Helpers\StrHelper as Str;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

abstract class EntityAbstract
{

    protected $fields = [];


    public function __call($method, $params = []){

        $methodPrefix = substr($method, 0, 3);

        if($methodPrefix !== 'get' && $methodPrefix !== 'set'){
            throw new \Exception($method . ' not allowed');
        }

        $field = str_replace($methodPrefix, '', $method);

        $field = Str::snake($field);

        if(!isset($this->fields[$field])){
            if($field !== 'fields' && $methodPrefix !== 'get'){
                throw new \Exception($field . ' not allowed');
            }
        }

        if($methodPrefix === 'get'){
            if($field === 'fields'){
                return $this->fields;
            }
            return $this->fields[$field];
        }

        if($methodPrefix === 'set'){
            $this->fields[$field] = $params[0];
            return $this;
        }


    }

    public function __set($name, $value)
    {
        $this->fields[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }else{
            if ($name === 'fields') {
                return $this->fields;
            }
        }

        return null;
    }
}
