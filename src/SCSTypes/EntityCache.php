<?php

/**
 * Class EntityCache
 * @package Subbclub\SCSTypes
 */

namespace Subbclub\SCSTypes;

class EntityCache
{
    public static $globalCache = [];

    /**
     * Сбор переменной
     * @param $_id
     * @param $bucketType
     * @return bool|mixed
     */
    public static function observe($_id, $bucketType)
    {
        $typeBucket = isset(self::$globalCache[$bucketType]);

        if ($typeBucket) {
            if (isset(EntityCache::$globalCache[$bucketType][(string)$_id])) {
                return EntityCache::$globalCache[$bucketType][(string)$_id];
            }
            return false;
        }
        return false;
    }

    /**
     * Обновление/сохранение переменной
     * @param Entity $object
     * @return bool
     */
    public static function keep(Entity $object)
    {
        $bucketType = $object->getClassName();
        $typeBucket = isset(self::$globalCache[$bucketType]);

        if (!$typeBucket) {
            self::bucket($bucketType);
        }

        EntityCache::$globalCache[$bucketType][(string)$object->getId()] = $object;
        return true;
    }

    /**
     * Заведение хранилища
     * @param $bucketType
     */
    public static function bucket($bucketType)
    {
        self::$globalCache[$bucketType] = [];
    }
}