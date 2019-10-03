<?php


namespace SchemaRegistrySync\Helpers;

use function FlixTech\SchemaRegistryApi\Requests\validateSchemaStringAsJson;

class AvroHelper
{

    public static function isValidJsonString($str) {

        if(!validateSchemaStringAsJson($str)){
            return false;
        }

        return true;
    }

}
