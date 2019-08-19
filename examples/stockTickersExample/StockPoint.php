<?php


use Subbclub\SCSTypes\Entity\EntityTypeMongo;

class StockPoint extends EntityTypeMongo
{
    protected static $fields = [
        "symbol" => 'string', //"AAPL"
        "name" => 'string', // "Apple Inc."
        "price" => "float", // "204.31"
        "currency" => 'string', // "USD"
        "price_open" => "float", // "204.40"
        "day_high" => "float", // "205.26"
        "day_low" => "float", // "203.86"
        "52_week_high" => "float", // "233.47"
        "52_week_low" => "float", // "142.00"
        "day_change" => "float", // "0.01"
        "change_pct" => "float", // "0.00"
        "close_yesterday" => "float", // "204.30"
        "market_cap" => "int", // "963378806784"
        "volume" => "int", // "2835608"
        "volume_avg" => "int", // "19496080"
        "shares" => "int", // "4715279872"
        "stock_exchange_long" => "string", //  "NASDAQ Stock Exchange"
        "stock_exchange_short" => "string", //  "NASDAQ"
        "timezone" => "string", //  "EDT"
        "timezone_name" => "string", //  "America/New_York"
        "gmt_offset" => "real", //  "-14400"
        "last_trade_time" => "date" //  "2019-04-29 09:52:01"
    ];
    protected static $references = [
        'StockMarker'
    ];
    protected $fieldOptions = [
        "last_trade_time" =>
            [
                'timezone' => 'dynamic',
                'timezoneFrom' => 'gmt_offset',
                'format' => "Y-m-d H:i:s"
            ],
    ];
    protected $arTypes = [

    ];
}