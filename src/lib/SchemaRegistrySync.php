<?php
namespace SchemaRegistrySync\Lib;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

class SchemaRegistrySync
{
    protected $schemaRegistryUrl;
    protected $client;
    public function __construct($schemaRegistryUrl)
    {
        $this->schemaRegistryUrl = $schemaRegistryUrl;
        $this->client = new Client(['base_uri' => $this->schemaRegistryUrl]);
    }

    public function sync(){

        $subjects = $this->getSubjects();

        foreach ($subjects as $subject){
            $versions = $this->getVersions($subject);
            foreach($versions as $version){
                $schema = $this->getSchema($subject, $version);
                dump($schema);
            }

        }


    }

    private function getSubjects(){

        $promise = $this->client->sendAsync(allSubjectsRequest());

        $promise->then(
            static function (ResponseInterface $response){
                return $response;
            }
        );

        $response = $promise->wait();

        return $this->getJsonFromResponseBody($response);
    }

    private function getVersions($subject){
        $promise = $this->client->sendAsync(allSubjectVersionsRequest($subject));

        $promise->then(
            static function (ResponseInterface $response){
                return $response;
            }
        );

        $response = $promise->wait();

        return $this->getJsonFromResponseBody($response);

    }

    private function getSchema($subject, $version){
        $promise = $this->client->sendAsync(singleSubjectVersionRequest($subject, $version));

        $promise->then(
            static function (ResponseInterface $response){
                return $response;
            }
        );

        $response = $promise->wait();

        return $this->getJsonFromResponseBody($response);

    }

    public function getSchemaRegistryUrl(){
        return $this->schemaRegistryUrl;
    }

    private function getJsonFromResponseBody(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        try {
            return \GuzzleHttp\json_decode($body, true);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                sprintf('%s - with content "%s"', $e->getMessage(), $body),
                $e->getCode(),
                $e
            );
        }
    }
}
