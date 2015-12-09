<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

/*
 *  Create an iterator on an Model
 *  Returned entry by the iterator is build with the model engine
 */
class ObjectIterator extends \MongoCursor
{
    private $objectType = null;
    private $collectionName = null;
    
    public function __construct($objectType, $filter=array(), $collectionName=null)
    {
        $this->objectType = $objectType;$objectType;
        if ($collectionName === null) {
            $collectionName = $objectType::collectionName;
        } else {
            $this->collectionName = $collectionName;
        }

        $databases = Conf::f('databases', array());
        $config = Conf::f('storage-mongodb-object', array());
        $dbName = isset($config['databases']) ? $config['databases'] : 'default';
        if (!isset($databases[$dbName])) {
            throw new \photon\db\UndefinedConnection(sprintf('The connection "%s" is not defined in the configuration.', $db));
        }
        
        $class = class_exists('\MongoClient') ? '\MongoClient' : '\Mongo';
        $conn = new $class($databases[$dbName]['server'], $databases[$dbName]['options']);
        $ns = $databases[$dbName]['database'] . '.' . $collectionName;
        $fields = array('_id' => 1);
        parent::__construct($conn, $ns, $filter, $fields);
    }
    
    public function current()
    {
        if ($this->collectionName === null) {
            return new $this->objectType(parent::current());
        } else {
            return new $this->objectType($this->collectionName, parent::current());
        }
    }
}

