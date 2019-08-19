<?php


use Subbclub\SCSTypes\Entity\EntityTypeMongo;

class StockMarker extends EntityTypeMongo
{

    protected static $fields = [
        "name" => 'string',
        "alias" => 'string',
        'code' => 'string'
    ];

    protected static $references = [
        'StockCategory',
    ];
}