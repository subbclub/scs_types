<?php


use Subbclub\SCSTypes\Entity\EntityTypeMongo;

class StockCategory extends EntityTypeMongo
{

    protected static $fields = [
        "name" => 'string',
        "alias" => 'string',
        'country' => 'string'
    ];

    protected static $references = [
        'StockCategoryType',
        'StockCategory',
        'StockMarker',
    ];
}