<?php
namespace SchemaRegistrySync\Lib;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use SchemaRegistrySync\Helpers\StrHelper;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

class SchemaRegistryParser
{
    protected $path;
    protected $data;
    public function __construct($path)
    {
        $this->path = $path;
        $this->data = unserialize(file_get_contents($this->path));

    }

    public function getData(){
        return $this->data;
    }

    public function getSubjects(){
        return array_keys((array)$this->data);
    }

    public function getVersions($subject){
        if(isset($this->data->$subject)){
            return array_keys((array)$this->data->$subject->versions);
        }

        return null;

    }
}
