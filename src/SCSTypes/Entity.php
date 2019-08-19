<?php
/**
 * Class Entity
 * @package Subbclub\SCSTypes
 */
namespace Subbclub\SCSTypes;


abstract class Entity
{
    protected static $references;
    protected $generate;
    protected $referencedLinks = [];

    public function __construct($_id = null, $generate = false)
    {
        $this->generate = $generate;
        if (!$this->generate) {
            $this->load($_id);
        }
    }

    /**
     * @param null|string $_id
     * @param bool $reload
     * @return mixed
     */
    abstract function load($_id = null, $reload = false);

    abstract public function toArray(): array;

    abstract public function save();

    abstract public function getId();

    abstract function __get($name);

    abstract function __set($name, $value);

    /**
     * @param Entity $entity
     * @return bool
     */
    public function referenceTo(Entity $entity)
    {
        $mainClass = get_class($entity);

        if (in_array($mainClass, self::getReferences())) {
            $this->referencedLinks[$mainClass][] = $entity;
            return true;
        }

        return false;
    }

    public static function getReferences()
    {
        /** @var Entity $class */
        $class = get_called_class();
        return $class::$references;
    }

    /**
     * @param $_id
     * @return Entity|null
     */
    public function getReference($_id)
    {
        foreach ($this->referencedLinks as $reference => $referencedLinks) {
            if (in_array($_id, $referencedLinks)) {
                $classObject = EntityCache::observe($_id, $reference);
                if (!$classObject) {
                    /** @var Entity $classObject */
                    $classObject = new $reference($_id);
                }
                return $classObject;
            }
        }
        return null;
    }

    public function getReferenceList()
    {

        $referenceList = [];
        foreach ($this->referencedLinks as $reference => $referenceLinkList) {
            $referenceList[$reference] = [];
            /** @var Entity $link */
            foreach ($referenceLinkList as $link) {
                $referenceList[$reference][] = $link->load();
            }
        }

        return $referenceList;
    }

    /**
     * @param $array
     */
    public function populateFrom($array)
    {
        foreach ($array as $item => $value) {
            $this->$item = $value;
        }
    }

    abstract public function getClassName();
}