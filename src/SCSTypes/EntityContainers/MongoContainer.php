<?php
/**
 *
 */

namespace Subbclub\SCSTypes\EntityContainers;


use Exception;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;
use ReflectionClass;
use ReflectionException;
use Subbclub\Libraries\Connectors\MongoDB_Options;

/**
 * Trait MongoContainer
 * @package Subbclub\SCSTypes\EntityContainers
 */
trait MongoContainer
{
    /**
     * @var Collection
     */
    protected static $collection;

    /**
     * @param $collectionName
     * @return bool
     */
    public static function overrideCollection($collectionName)
    {
        try {
            self::$collection = MongoDB_Options::get_collection($collectionName);
        } catch (Exception $exception) {
            return false;
        }
        return false;
    }

    /**
     * @param array $filter
     * @param array $options
     * @param bool $array
     * @param bool $debug
     * @return array|Cursor
     */
    public static function find($filter = [], $options = [], $array = false, $debug = false)
    {
        self::loadCollection();
        $findResults = self::$collection->find($filter, $options);
        if ($array) {
            return $findResults->toArray();
        }
        return $findResults;
    }

    /**
     * @return bool
     */
    public static function loadCollection()
    {
        if (!isset(self::$collection)) {
            $collectionName = self::getCollection();

            try {
                self::$collection = MongoDB_Options::get_collection($collectionName);
            } catch (Exception $exception) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool|string
     */
    public static function getCollection()
    {
        try {
            return (new ReflectionClass(get_called_class()))->getName();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * @param array $filter
     * @param array $options
     * @return array|Cursor
     */
    public static function findOne($filter = [], $options = [])
    {
        self::loadCollection();
        $findResults = self::$collection->findOne($filter, $options);
        return $findResults;
    }

    /**
     * @param $filter
     * @param $updateOptions
     * @return UpdateResult
     */
    public static function updateMany($filter, $updateOptions)
    {
        self::loadCollection();

        return self::$collection->updateMany($filter, ['$set' => $updateOptions]);
    }

    /**
     * @param $filter
     * @param $updateOptions
     * @return UpdateResult
     */
    public static function updateOne($filter, $updateOptions)
    {
        self::loadCollection();

        return self::$collection->updateOne($filter, ['$set' => $updateOptions]);
    }


    /**
     * @param $filter
     * @param array $insertOptions
     * @return InsertOneResult
     */
    public static function insertOne($filter, $insertOptions = [])
    {
        self::loadCollection();

        return self::$collection->insertOne($filter, $insertOptions);
    }

    /**
     * @param $filter
     * @return bool
     */
    public static function deleteOne($filter)
    {
        self::loadCollection();
        $deletionResult = self::$collection->deleteOne($filter);
        if ($deletionResult->getDeletedCount() == 1) {
            return true;
        }
        return false;
    }

    /**
     * @param $filter
     * @return bool
     */
    public static function deleteMany($filter)
    {
        self::loadCollection();
        $deletionResult = self::$collection->deleteMany($filter);
        if ($deletionResult->getDeletedCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param array $filter
     * @return int
     */
    public static function count($filter = [])
    {
        self::loadCollection();
        return self::$collection->countDocuments($filter);
    }
}