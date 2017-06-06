<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;


/*
 * Create a object from DB, track update and apply on demand
 */
class Object implements \ArrayAccess
{
    /*
     *  The collection name to use for this object
     */
    const collectionName = 'null';
    
    /*
     *  List mandatory fields need to be set before a DB commit
     */
    protected $mandatoryFields = array();
    
    protected $__filter;
    protected $__db;    
    protected $__collection;
    protected $__data;
    protected $__pending;

    /*
     *  Construct a new object with a empty filter will create a new object
     *  If the filter is not empty, the constructor will load the object from the database.
     */
    public function __construct($filter=null)
    {
        $config = Conf::f('storage-mongodb-object', array());
        $db = isset($config['databases']) ? $config['databases'] : 'default';
        $db = DB::get($db);

        $this->__db = $db;
        $this->__collection = $db->selectCollection($this::getCollectionName());
        $this->__filter = $filter;
        $this->__data = array();
        $this->__pending = array();

        if ($filter === null) {
            $this->initObject();
        } else {
            $this->__data = $this->__collection->findOne($this->__filter);
            if ($this->__data === null) {
                throw new Exception("Object not found", 404);
            } else {
                $this->__filter = array('_id' => $this->__data['_id']);
                $this->postLoad();
                $this->__pending = array(
                    '$set' => array(),
                    '$unset' => array(),
                );
            }
        }
    }

    /*
     *  Return the collection name for this object
     *  Override this method for object with dynamic collection, like data points and vectors
     */
    static public function getCollectionName()
    {
        return static::collectionName;
    }    

    protected function initObject()
    {
        // Extends this methods to initialize a empty object of your
    }

    protected static function newId()
    {
        // Extends this methods to use your id generator instead of MongoId
        return new \MongoDB\BSON\ObjectID;
    }

    protected function postCreate()
    {
        // Extends to perform operation just after the object is created in DB
    }

    protected function postLoad()
    {
        // Extends to perform operation just after the DB extraction
    }
    
    protected function preSave()
    {
        // Extends to perform operation just before a save, like update a last modified time field
    }

    protected function postSave()
    {
        // Extends to perform operation just after a save, like add entry in timeline
    }

    protected function preDelete()
    {
        // Extends to perform operation just before a delete
    }

    public function __get($name)
    {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        if (array_key_exists($name, $this->__data)) {
            return $this->__data[$name];
        }

        return null;
    }

    public function __isset($name)
    {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        return array_key_exists($name, $this->__data);
    }

    public function __unset($name)
    {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        unset($this->__data[$name]);
        $this->__pending['$unset'][$name] = 1;
    }

    public function __set($name, $value)
    {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        if ($this->__filter !== null && $name === '_id') {
            throw new Exception('Property is read-only');
        }

        $preSet = 'preSet_'.$name;
        if (method_exists($this, $preSet)) {
            $value = $this->$preSet($value);
        }

        $this->__data[$name] = $value;
        $this->__pending['$set'][$name] = $value;
    }

    public function setFields($fields)
    {
        foreach($fields as $k => $v) {
            $this->{$k} = $v;
        }
    }

    public function save()
    {
        if ($this->__data === null) {
            throw new Exception('Cannot save to a deleted object');
        }

        // Ensure all mandatory fields are set
        foreach($this->mandatoryFields as $field) {
            if (isset($this->{$field}) === false) {
                throw new Exception('Missing mandatory field: ' . $field);
            }
        }

        // Object hook
        $this->preSave();

        if (isset($this->__pending['$unset']) && count($this->__pending['$unset']) === 0)
            unset($this->__pending['$unset']);

        if (isset($this->__pending['$set']) && count($this->__pending['$set']) === 0)
            unset($this->__pending['$set']);

        if ($this->__filter === null) {
            // Create
            if (isset($this->__data['_id']) === false) {
                $id = $this::newId();
                if ($id !== null) {
                    $this->__data['_id'] = $id;
                }
            }

            $r = $this->__collection->insertOne($this->__data);
            $this->__filter = array('_id' => $this->__data['_id']);

            // Object hook
            $this->postCreate();
        } else {
            // Update
            if (isset($this->__pending['$set']) || isset($this->__pending['$unset'])) {
                $this->__collection->updateOne(
                    array('_id' => $this->__data['_id']),
                    $this->__pending
                );
            }
        }

        // Object hook
        $this->postSave();

        $this->__pending['$set'] = array();
        $this->__pending['$unset'] = array();
    }

    public function delete()
    {
        if ($this->__data === null)
            return false;

        $this->preDelete();
        
        $this->__collection->deleteOne(
            array('_id' => $this->__data['_id'])
        );

        $this->__data = null;
        return true;
    }

    /*
     *  Return true if the Model collection is empty
     *         false otherwise
     */
    public static function isEmpty()
    {
        $config = Conf::f('storage-mongodb-object', array());
        $db = isset($config['databases']) ? $config['databases'] : 'default';
        $db = DB::get($db);

        $collection = $db->selectCollection($this->getCollectionName());
        $it = $collection->find();
        $it->limit(1);
        $it->rewind();
        return $it->hasNext() === false;
    }

    /*
     *  Return the number of object that match the filter
     */
    static public function count($filter=array())
    {
        $config = Conf::f('storage-mongodb-object', array());
        $db = isset($config['databases']) ? $config['databases'] : 'default';
        $db = DB::get($db);
        $collection = $db->selectCollection(static::getCollectionName());

        return $collection->count($filter);
    }

    /*
     *  ArrayAccess methods
     */
    public function offsetSet($offset, $value) {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        if (is_null($offset)) {
            $this->__data[] = $value;
        } else {
            $this->__data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        return isset($this->__data[$offset]);
    }

    public function offsetUnset($offset) {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        unset($this->__data[$offset]);
    }

    public function offsetGet($offset) {
        if ($this->__data === null) {
            throw new Exception('Cannot access to a deleted object');
        }

        return isset($this->__data[$offset]) ? $this->__data[$offset] : null;
    }

    public function __toString()
    {
        $filter = ($this->__filter === null) ? 'new object' : json_encode($this->__filter);
        return '[' . get_class($this) . '] : ' . $filter;
    }
}

