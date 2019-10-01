<?php
namespace SchemaRegistrySync\Entities;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use SchemaRegistrySync\Helpers\StrHelper;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

class Schema extends EntityAbstract
{
    protected $fields = [
        'id'        => '',
        'fields'    => [],
        'namespace' => '',
        'type'      => 'record',
        'name'      => '',
        'raw_schema'      => ''
    ];


}
