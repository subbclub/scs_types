<?php

/**
 *
 */

namespace Subbclub\SCSTypes\Entity;


use DateTime;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Subbclub\SCSTypes\Entity;
use Subbclub\SCSTypes\EntityCache;
use Subbclub\SCSTypes\EntityContainers\MongoContainer;

/**
 * Class EntityTypeMongo
 * @package Subbclub\SCSTypes\Entity
 */
abstract class EntityTypeMongo extends Entity
{
    use MongoContainer;
    protected static $fields;
    public $data;
    /**
     * @var ObjectId
     */
    protected $_id;
    /**
     * @var UTCDateTime
     */
    protected $date;
    protected $arTypes;
    protected $fieldOptions;
    protected $delayFields;
    protected $delayFieldsData;

    /**
     * @param array $params
     * @param array $options
     * @param bool $array
     * @return array
     */
    public static function getList($params = [], $options = [], $array = false)
    {
        foreach ($params as $paramName => $paramValue) {
            if (in_array($paramName, array_keys(self::getFields()))) {
                unset($params[$paramName]);
                $paramName = 'data.' . $paramName;
                $params[$paramName] = $paramValue;
            }
        }

        $resultFromDB = self::find($params, $options, true);

        if (empty($resultFromDB)) {
            return [];
        }

        if ($array) {
            /** @var array $arrayOfArrays */
            $arrayOfArrays = [];
        } else {
            /** @var array $arrayOfObjects */
            $arrayOfObjects = [];

        }
        $class = get_called_class();

        foreach ($resultFromDB as $item) {
            $newObject = self::populateObject($class, $item);

            if ($array) {
                $arrayOfArrays[] = $newObject->toArray();
            } else {
                $arrayOfObjects[] = $newObject;
            }
        }

        if ($array) {
            /** @var array $arrayOfArrays */

            return $arrayOfArrays;
        } else {
            /** @var array $arrayOfObjects */

            return $arrayOfObjects;
        }
    }

    /**
     * Поля коллекции
     * @return mixed
     */
    public static function getFields()
    {
        /** @var EntityTypeMongo $class */
        $class = get_called_class();
        return $class::$fields;
    }

    /**
     * @param callable $class
     * @param array $resultFromDbItem
     * @return EntityTypeMongo
     */
    public static function populateObject($class, $resultFromDbItem)
    {
        /** @var EntityTypeMongo $newObject */
        $newObject = new $class(null, true);

        $newObject->populateFrom((array)$resultFromDbItem['data']);

        $newObject->_id = $resultFromDbItem['_id'];

        foreach (self::getReferences() as $reference) {

            if (isset($resultFromDbItem[$reference])) {
                /** @var BSONArray $Reference */
                $Reference = $resultFromDbItem[$reference];
                if ($Reference->count() > 0) {
                    foreach ($Reference as $insideItem) {
                        /** @var EntityTypeMongo $uLinkedClass */
                        $uLinkedClass = new $reference(null, true);
                        $uLinkedClass->_id = $insideItem;
                        $newObject->referenceTo($uLinkedClass);
                    }
                }
            }
        }

        EntityCache::keep($newObject);
        return $newObject;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->data as $field => $type) {
            $result[$field] = $this->$field;
        }

        return $result;
    }

    /**
     * В этот метод имеет смысл передавать _id только в случае,
     * если есть необходимость проверить конкретный элемент на совпадение с нужными параметрами.
     * Если нужно просто получить элемент, используем конструктор с $_id или load
     *
     * @param array $params
     * @param array $options
     * @return array|boolean|EntityTypeMongo
     */
    public static function getOne($params = [], $options = [])
    {
        foreach ($params as $paramName => $paramValue) {
            if (in_array($paramName, array_keys(self::getFields()))) {
                unset($params[$paramName]);
                $paramName = 'data.' . $paramName;
                $params[$paramName] = $paramValue;
            }
        }

        $resultFromDB = self::findOne($params, $options);

        if (empty($resultFromDB)) {
            return false;
        }


        $newObject = self::populateObject(get_called_class(), $resultFromDB);
        return $newObject;
    }

    /**
     * Сохранение
     * @return bool|EntityTypeMongo
     * @throws EntityTypeMongoException
     */
    function save()
    {
        $toDB = [];
        foreach (self::$fields as $field => $type) {
            if ($field == 'created') {
                continue;
            }

            if ($field == 'lastUsed') {
                $toDB[$field] = new UTCDateTime();
                continue;
            }

            switch ($type) {
                case 'date':
                    /** @var DateTime $fieldData */
                    $fieldData = $this->data[$field];
                    $toDB[$field] = new UTCDateTime($fieldData);
                    break;
                case 'int':
                    /** @var float $fieldData */
                    $fieldData = $this->data[$field];
                    $toDB[$field] = intval($fieldData);
                    break;
                case 'float':
                    /** @var float $fieldData */
                    $fieldData = $this->data[$field];
                    $toDB[$field] = floatval($fieldData);
                    break;
                case 'id':
                    /** @var ObjectId $fieldData */
                    $fieldData = new ObjectId($this->data[$field]);
                    $this->data[$field] = $fieldData;
                    break;
                case 'arType':
                    if (!in_array($this->data[$field], $this->arTypes[$field])) {
                        throw new EntityTypeMongoException('Key for ' . $this->data[$field] . ' not found on ' . $field, 10100);
                    }
                    $fieldData = array_search($this->data[$field], $this->arTypes[$field], true);
                    $toDB[$field] = $fieldData;
                    break;
                default:
                case 'string':
                    $fieldData = $this->data[$field];
                    $toDB[$field] = $fieldData;
                    break;
            }
        }


        if (!isset($this->_id)) {
            $insertionResult = self::insertOne($toDB);
            $this->_id = $insertionResult->getInsertedId();
            if ($insertionResult->getInsertedCount() == 1) {
                return $this;
            } else {
                return false;
            }
        }

        $dataSet = [];

        $dataSet['data'] = $toDB;

        if (!empty(self::getReferences())) {
            foreach (self::getReferences() as $reference) {
                $dataSet[$reference] = [];
                if (isset($this->referencedLinks) && isset($this->referencedLinks[$reference])) {
                    /** @var EntityTypeMongo $referencedLink */
                    foreach ($this->referencedLinks[$reference] as $referencedLink) {
                        $dataSet[$reference][] = $referencedLink->getId();
                    }
                }
            }
        }

        $updateResult = self::updateOne(['_id' => $this->_id], $dataSet);
        if ($updateResult->getModifiedCount() == 1) {


            $this->load(null, true);
            return $this;
        } else {
            return false;
        }
    }

    /**
     * @return ObjectId
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Загрузка
     * @param null|string $_id
     * @param bool $reload
     * @return bool|EntityTypeMongo
     */
    public function load($_id = null, $reload = false)
    {
        $object = false;
        if (!is_null($_id)) {
            $this->_id = new ObjectId($_id);
        }

        if (isset($this->_id) && !$reload) {
            $className = $this->getClassName();
            $object = EntityCache::observe($this->_id, $className);
            return $object;
        }

        if (!$object) {

            if (!isset($this->_id)) {
                $this->_id = self::insertOne(['created' => new UTCDateTime(), 'lastUsed' => new UTCDateTime()])->getInsertedId();
                return $this;
            } else {
                $entity = self::findOne(['_id' => $this->_id]);
                foreach (self::$fields as $field => $type) {
                    if ($field == 'created' || $field == 'lastUsed') {
                        /** @var UTCDateTime $fieldData */
                        $this->data[$field] = $fieldData->toDateTime();
                        continue;
                    }

                    $fieldData = $entity[$field];
                    switch ($type) {
                        case 'date':
                            /** @var UTCDateTime $fieldData */
                            $this->data[$field] = $fieldData->toDateTime();
                            break;
                        case 'int':
                            /** @var float $fieldData */
                            $this->data[$field] = intval($fieldData);
                            break;
                        case 'float':
                            /** @var float $fieldData */
                            $this->data[$field] = floatval($fieldData);
                            break;
                        case 'id':
                            /** @var ObjectId $fieldData */
                            $this->data[$field] = $fieldData->__toString();
                            break;
                        case 'arType':
                            $fieldData = intval($fieldData);
                            $this->data[$field] = $this->arTypes[$field][$fieldData];
                            break;
                        default:
                        case 'string':
                            $this->data[$field] = (string)$fieldData[$field];
                            break;
                    }
                }
                EntityCache::keep($this);
                return $this;
            }
        }
        return false;
    }

    /**
     * @return bool|string
     */
    public function getClassName()
    {
        return self::getCollection();
    }

    /**
     * @param $field
     * @return mixed
     */
    public function __get($field)
    {

        if (array_key_exists($field, $this->data)) {

            return $this->data[$field];
        }
        return false;
    }

    /**
     * Fluent set
     * @param $field
     * @param $value
     * @return $this
     * @throws EntityTypeMongoException
     */
    public function __set($field, $value)
    {
        $fieldType = self::$fields[$field];
        switch ($fieldType) {
            case 'date':
                try {
                    if (isset($this->fieldOptions[$field])) {

                        $timezone = null;

                        if ($this->fieldOptions[$field]['timezone']) {
                            if ($this->fieldOptions[$field]['timezone'] == 'dynamic') {
                                $timezoneFrom = $this->fieldOptions[$field]['timezoneFrom'];
                                if (isset($this->{$timezoneFrom})) {
                                    $timezone = $this->{$timezoneFrom};
                                } else {
                                    $this->delayFields[$field] = $timezoneFrom;
                                    $this->delayFieldsData[$field] = $value;
                                    return $this;
                                }
                            } else {
                                $timezone = $this->fieldOptions[$field]['timezone'];
                            }
                        }
                        $value = DateTime::createFromFormat($this->fieldOptions[$field]['format'], $value, $timezone);
                    }
                } catch (Exception $e) {
                    throw new EntityTypeMongoException($e->getMessage(), $e->getCode());
                }
                break;
        }

        $this->data[$field] = $value;
        if (in_array($field, $this->delayFields)) {
            $key = array_search($field, $this->delayFields);
            $this->$key = $this->delayFieldsData[$key];
        }
        return $this;
    }

    /**
     * Проверка наличия (для соответствия стандартной модели магических методов, например twig её использует)
     * @param $field
     * @return bool
     */
    public function __isset($field)
    {
        if (array_key_exists($field, $this->data)) {
            return true;
        }
        return false;
    }

    /**
     * Получение списка ассоциированных коллекций и сущностей из них
     *
     * @param null|array $filter
     * @return array
     */
    public function getAssociates($filter = null)
    {
        $this->load(null, true);
        /**
         * Сначала забираем то, что сохранено у себя самих
         */
        $refList = $this->getReferenceList();

        /**
         * Теперь пробуем получить то, что зашито у других
         */

        if (!is_null($filter)) {
            if (!is_array($filter)) {
                $filter = array($filter);
            }
        } else {
            $filter = self::getReferences();
        }

        foreach (self::getReferences() as $referenceClassName) {
            if (in_array($referenceClassName, $filter)) {
                self::overrideCollection($referenceClassName);
                $filterFind = [
                    self::getCollection() => [
                        '$elemMatch' => [
                            '$eq' => $this->_id
                        ]
                    ]
                ];

                $linkedResults = self::find($filterFind, [], true, true);
                foreach ($linkedResults as $linkedResult) {
                    $newObject = self::populateObject($referenceClassName, $linkedResult);
                    $refList[$referenceClassName][] = $newObject;
                }
            }
        }

        return $refList;
    }

    /**
     * Снос конкретного объекта из коллекции
     * @return bool
     */
    public function remove()
    {
        $deletionResult = self::deleteOne(['_id' => $this->_id]);
        return $deletionResult;
    }

}


class EntityTypeMongoException extends Exception
{

}