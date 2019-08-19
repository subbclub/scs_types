<?php


use Subbclub\SCSTypes\Entity\EntityTypeMongo;

class StockCategoryType extends EntityTypeMongo
{
    protected static $fields = [
        "name" => 'string',
        "alias" => 'string'
    ];

    protected static $references = [
        'StockCategory'
    ];
}