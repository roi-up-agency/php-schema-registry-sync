<?php
namespace SchemaRegistrySync\Sync;

use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Test\Requests\FunctionsTest;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use SchemaRegistrySync\Entities\Field;
use SchemaRegistrySync\Entities\Schema;
use SchemaRegistrySync\Entities\Subject;
use SchemaRegistrySync\Entities\Version;
use SchemaRegistrySync\Faker\AvroFaker;
use SchemaRegistrySync\Helpers\StrHelper;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectsRequest;
use function FlixTech\SchemaRegistryApi\Requests\allSubjectVersionsRequest;
use function FlixTech\SchemaRegistryApi\Requests\singleSubjectVersionRequest;

class SchemaRegistrySync
{
    protected $schemaRegistryUrl;
    protected $client;
    protected $keyValueDiff = false;
    protected $withExamples = false;
    protected $localizedExamples;

    public function __construct($schemaRegistryUrl)
    {
        $this->schemaRegistryUrl = $schemaRegistryUrl;
        $this->client = new Client(['base_uri' => $this->schemaRegistryUrl]);
    }

    public function withExamples($locale = 'en_US'){
        $this->localizedExamples = $locale;
        $this->withExamples = true;
        return $this;
    }

    public function sync(){

        $subjects = $this->getSubjects();

        $arrSubjects = [];

        foreach ($subjects as $s){

            $topic = $s;

            if(StrHelper::endsWith($s, '-value')){
                $topic = str_replace('-value', '', $s);
            }else if(StrHelper::endsWith($s, '-key')){
                continue;
            }

            $subject = new Subject();
            $subject->name = $s;
            $subject->topic = $topic;
            $subject->versions = [];

            $versions = $this->getVersions($s);

            $arrVersion = [];
            foreach($versions as $v){
                $version = new Version();

                $version->version = $v;


                $sc = $this->getSchema($s, $v);

                $schema = new Schema();
                $schema->id = $sc['id'];
                $scData = json_decode($sc['schema']);
                $schema->name = $scData->name;
                $schema->type = $scData->type;
                $schema->namespace = $scData->namespace;
                $schema->raw_schema = $sc['schema'];
                $schema->fake_examples = $this->withExamples ? (new AvroFaker($this->localizedExamples))->generateMultiples(json_decode($sc['schema']), rand(1, 3)) : [];

                $arrFields = [];
                foreach($scData->fields as $f){
                    $field = new Field();
                    $field->name = $f->name;
                    $field->type = $f->type;
                    $field->doc  = isset($t->doc) ? $t->doc : '';
                    $arrFields[] = $field;
                }
                $schema->fields = $arrFields;

                $version->schema = $schema;

                $arrVersion[] = $version;

            }

            $subject->versions = $arrVersion;

            $arrSubjects[] = $subject;
        }

        return serialize($arrSubjects);

    }

    private function getSubjects(){
        return $this->getResponse($this->client->sendAsync(allSubjectsRequest()));
    }

    private function getVersions($subject){
        return $this->getResponse($this->client->sendAsync(allSubjectVersionsRequest($subject)));
    }

    private function getSchema($subject, $version){
        return $this->getResponse($this->client->sendAsync(singleSubjectVersionRequest($subject, $version)));

    }

    private function getResponse($promise){
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

    public function setKeyValueDiff(bool $value){
        $this->keyValueDiff = $value;
    }
}
