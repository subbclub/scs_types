<?php
/**
 * MongoDB_Options Connector Class
 */

namespace Subbclub\Libraries\Connectors;

use MongoDB\Client;
use MongoDB\Collection;

class MongoDB_Options
{
    static $Host;
    static $User;
    static $Password;
    static $DB;
    static $options;
    static $context;
    static $replicaSet;

    /**
     * @param $collectionName
     * @return Collection
     */
    public static function get_collection($collectionName)
    {
        $client = MongoDB_Options::init();
        $database = $client->selectDatabase(MongoDB_Options::getDB());
        $collection = $database->selectCollection($collectionName);
        return $collection;
    }

    /**
     * @return Client
     */
    public static function init()
    {
        $uri = 'mongodb://';

        if (self::getUser()) {
            $uri = self::getUser();
        }
        if (self::getUser() && self::getPassword()) {
            $uri .= ':' . self::getPassword() . '@';
        }
        $uri .= self::getHost() . '/' . self::getDB();
        if (self::getReplicaSet()) {
            $uri .= '?replicaSet=' . self::getReplicaSet();
        }

        if (self::getContext()) {
            return new Client($uri, self::getOptions(), ['context' => stream_context_create(self::getContext())]);
        }

        return new Client($uri, self::getOptions());
    }

    /**
     * @return mixed
     */
    public static function getUser()
    {
        return self::$User;
    }

    /**
     * @param mixed $User
     */
    public static function setUser($User)
    {
        self::$User = $User;
    }

    /**
     * @return mixed
     */
    public static function getPassword()
    {
        return self::$Password;
    }

    /**
     * @param mixed $Password
     */
    public static function setPassword($Password)
    {
        self::$Password = $Password;
    }

    /**
     * @return mixed
     */
    public static function getHost()
    {
        return self::$Host;
    }

    /**
     * @param mixed $Host
     */
    public static function setHost($Host)
    {
        self::$Host = $Host;
    }

    /**
     * @return mixed
     */
    public static function getDB()
    {
        return self::$DB;
    }

    /**
     * @param mixed $DB
     */
    public static function setDB($DB)
    {
        self::$DB = $DB;
    }

    /**
     * @return mixed
     */
    public static function getReplicaSet()
    {
        return self::$replicaSet;
    }

    /**
     * @param mixed $replicaSet
     */
    public static function setReplicaSet($replicaSet)
    {
        self::$replicaSet = $replicaSet;
    }

    /**
     * @return mixed
     */
    public static function getContext()
    {
        return self::$context;
    }

    /**
     * @param $context
     */
    public static function setContext($context)
    {
        self::$context = $context;
    }

    /**
     * @return mixed
     */
    public static function getOptions()
    {
        return self::$options;
    }

    /**
     * @param mixed $options
     */
    public static function setOptions($options)
    {
        self::$options = $options;
    }
}