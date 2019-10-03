<?php
namespace SchemaRegistrySync\Sync;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use SchemaRegistrySync\Faker\AvroFaker;
use SchemaRegistrySync\Helpers\StrHelper;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

class SchemaRegistryHandler
{
    protected $path;
    protected $data;
    public function __construct($path)
    {
        $this->path = $path;
        $this->data = unserialize(file_get_contents($this->path));

    }

    public function data(){
        return $this->data;
    }

    public function subjects(){
        $subjects = [];

        array_walk($this->data, function($item) use (&$subjects){
           $subjects[] = $item->name;
        });

        return $subjects;
    }

    public function versions($subject){
        $versions = [];

        array_walk($this->data, function($item) use ($subject, &$versions){
            if($subject === $item->name){
                foreach($item->versions as $version){
                    $versions[] = $version->version;
                }
                return;
            }
        });

        return $versions;

    }

    public function schema($subject, $versionNumber){
        $schema = null;

        array_walk($this->data, function($item) use ($subject, $versionNumber, &$schema){
            if($subject === $item->name){
                foreach($item->versions as $version){
                    if($versionNumber === $version->version){
                        $schema = $version->schema;
                        return;
                    }

                }
            }
        });

        return $schema;
    }

    public function fake($schema, $locale = 'en_US', $numerOfFakes = 1){
        return $numerOfFakes > 1 ? (new AvroFaker($locale))->generate($schema) : (new AvroFaker($locale))->generateMultiples($schema, $numerOfFakes);
    }
}
