<?php

declare(strict_types=1);

namespace SchemaRegistrySync\Faker;

use Faker\Provider\Uuid;
use SchemaRegistrySync\Helpers\AvroHelper;
use SchemaRegistrySync\Helpers\StrHelper;
use function call_user_func_array;
use function dirname;
use Faker\Factory;
use Faker\Provider\Base;
use Faker\Provider\DateTime;
use Faker\Provider\Internet;
use Faker\Provider\Lorem;
use function file_exists;
use function file_get_contents;
use function is_callable;
use function json_decode;
use function substr;

final class AvroFaker
{
    /**
     * type-fake method map
     *
     * @var array
     */
    private $fakers = [
        'null'      => 'fakeNull',
        'boolean'   => 'fakeBoolean',
        'int'       => 'fakeInteger',
        'long'      => 'fakeInteger',
        'float'     => 'fakeFloat',
        'double'    => 'fakeDouble',
        'string'    => 'fakeString',
        'enum'      => 'fakeEnum',
        'array'     => 'fakeArray',
        'record'    => 'fakeRecord'
    ];

    protected $cachedInstances = [];
    /**
     * @var string
     */
    private $fake;
    private $location;


    public function __construct($location = 'en_US')
    {
        $this->location = $location;
    }

    public function generateMultiples($schema, $numberOfExamples = 1){
        $examples = [];
        for($i = 0; $i < $numberOfExamples; $i++){
            $examples[] = $this->generate($schema);
        }

        return $examples;
    }

    /**
     * Create fake data with AVRO schema
     *
     * @param object $schema
     *
     * @throws InvalidArgumentException Throw when unsupported type specified
     */
    public function generate($schema)
    {

        if(!AvroHelper::isValidJsonString(json_encode($schema))){
            throw new \InvalidArgumentException('The provided schema is not a valid Json String');
        }

        switch ($schema->type){
            case is_object($schema->type):
                $type = $this->getTypeFromObject($schema->type);
                break;
            case is_array($schema->type):
                $type = Base::randomElement($schema->type);
                if(is_object($type)){

                    $newType = $this->getTypeFromObject($type);

                    if ($newType === 'enum') {
                        return Base::randomElement($type->symbols);
                    }

                    $type = $newType;
                }
            break;
            default:
                $type = $schema->type;
                break;
        }

        if (! isset($this->fakers[$type])) {
            throw new UnsupportedTypeException($type);
        }

        $faker = [$this, $this->fakers[$type]];
        if (is_callable($faker)) {
            return call_user_func($faker, $schema);
        }

        throw new \LogicException;
    }

    private function getTypeFromObject($schemaObjectType){
        if(isset($schemaObjectType->type)){
            return $schemaObjectType->type;
        }else{
            throw new \Exception("Type not found");
        }
    }

    public function getMaximum($schema) : int
    {
        return (int) mt_getrandmax();
    }

    public function getMinimum($schema) : int
    {
        return (int) 0;
    }

    public function getRandomSchema()
    {
        $fakerNames = array_keys($this->fakers);

        return (object) [
            'type' => Base::randomElement($fakerNames)
        ];
    }

    public function getInternetFakerInstance() : Internet
    {
        return new Internet(Factory::create());
    }

    public function getFormattedValue($schema)
    {
        switch ($schema->name) {

            case StrHelper::endsWith(strtolower($schema->name), 'id'):
                return Base::randomElement([Uuid::uuid(), (string)Base::randomNumber(5)]);
            // Date representation, as defined by RFC 3339, section 5.6.
            case 'date-time':
                return DateTime::dateTime()->format(DATE_RFC3339);
            case 'expiresAt':
                return (string)time();
            // Internet email address, see RFC 5322, section 3.4.1.
            case 'email':
                return $this->getInternetFakerInstance()->safeEmail();
            // Internet host name, see RFC 1034, section 3.1.
            case 'token':
                return base64_encode(Lorem::shuffleString(str_replace(' ', '', Lorem::words(12, true))));
            case 'hostname':
                return $this->getInternetFakerInstance()->domainName();
            // IPv4 address, according to dotted-quad ABNF syntax as defined in RFC 2673, section 3.2.
            case 'ipv4':
                return $this->getInternetFakerInstance()->ipv4();
            // IPv6 address, as defined in RFC 2373, section 2.2.
            case 'ipv6':
                return $this->getInternetFakerInstance()->ipv6();
            // A universal resource identifier (URI), according to RFC3986.
            case 'uri':
            case 'url':
                return $this->getInternetFakerInstance()->url();
            case 'latitude':
                return (string)$this->getLocalizedFaker('Address')->latitude();
                break;
            case 'longitude':
                return (string)$this->getLocalizedFaker('Address')->longitude();
                break;
            case 'city':
                return $this->getLocalizedFaker('Address')->city();
                break;
            case 'country':
                return $this->getLocalizedFaker('Address')->country();
                break;
            case 'state':
                return $this->getLocalizedFaker('Address')->state();
                break;
            case 'address':
                return $this->getLocalizedFaker('Address')->address();
                break;
            case 'zip_code':
            case 'zipcode':
            case 'postcode':
            case 'post_code':
                return (string)$this->getLocalizedFaker('Address')->postcode();
                break;
            default:
                return null;
        }
    }

    /**
     * @return string[] Field names
     */
    public function getFields(\stdClass $schema) : array
    {
        $fieldNames = [];
        foreach($schema->fields as $field){
            $fieldNames[] = $field->name;
        }
        return $fieldNames;
    }

    private function fakeNull()
    {
        return null;
    }

    private function fakeBoolean() : bool
    {
        return Base::randomElement([true, false]);
    }

    private function fakeInteger(\stdClass $schema) : int
    {
        $minimum = $this->getMinimum($schema);
        $maximum = $this->getMaximum($schema);

        return Base::numberBetween($minimum, $maximum);
    }

    private function fakeNumber(\stdClass $schema)
    {
        $minimum = $this->getMinimum($schema);
        $maximum = $this->getMaximum($schema);

        return Base::randomFloat(null, $minimum, $maximum);
    }

    private function fakeString(\stdClass $schema) : string
    {

        $value = $this->getFormattedValue($schema);

        if($value !== null){
            return $value;
        }

        return Lorem::text(rand(10, 25));
    }

    private function fakeArray(\stdClass $schema)
    {
        if (! isset($schema->type->items)) {
            $subschema = $this->getRandomSchema();

        } elseif (is_object($schema->type->items)) {
            $subschema = $schema->type->items;
        } else {
            return $this->getDataFromSimpleType($schema->type->items);
        }

        $dummies = [];
        $itemSize = Base::numberBetween(($schema->minItems ?? 1), $schema->maxItems ?? 8);

        for ($i = 0; $i < $itemSize; $i++) {
            $dummies[] = $this->generate($subschema);
        }

        return ($schema->uniqueItems ?? false) ? array_unique($dummies) : $dummies;
    }

    private function fakeRecord(\stdClass $schema) : \stdClass
    {

        $properties = $schema->fields ?? new \stdClass();

        $dummy = new \stdClass();

        foreach ($properties as $property) {
            $dummy->{$property->name} = $this->generate($property);
        }

        return $dummy;
    }

    private function getDataFromSimpleType($type){

        switch ($type){
            case 'null':
                return null;
                break;
            case 'boolean':
                return (bool)rand(0,1);
                break;
            case 'int':
                return Base::numberBetween(0, 1500);
                break;
            case 'float':
                return Base::randomFloat(2, 0, 1500);
                break;
            case 'double':
                return Base::randomNumber();
                break;
            case 'string':
                return Lorem::text(15);
                break;
            default:
                throw new \Exception($type . ' not supported');
                break;
        }
    }

    private function getLocalizedFaker($entity){
        $className = '\\Faker\\Provider\\' . $this->location . '\\' . $entity;

        if(isset($this->cachedInstances[$className])){
            return $this->cachedInstances[$className];
        }

        $generator = \Faker\Factory::create($this->location);
        $this->cachedInstances[$className] =  new $className($generator);

        return $this->cachedInstances[$className];
    }
}
