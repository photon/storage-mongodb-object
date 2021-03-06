<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

/*
 *  Create an iterator on an Model
 *  Returned entry by the iterator is build with the model engine
 */
class ObjectIterator extends \IteratorIterator
{
    private $objectType = null;
    private $collectionName = null;
    private $it = null;

    public function __construct($objectType, $filter=array(), $options=array(), $collectionName=null)
    {
        $this->objectType = $objectType;
        if ($collectionName === null) {
            $collectionName = $objectType::getCollectionName();
        } else {
            $this->collectionName = $collectionName;
        }

        $databases = Conf::f('databases', array());
        $config = Conf::f('storage-mongodb-object', array());
        $dbName = isset($config['databases']) ? $config['databases'] : 'default';

        $db = DB::get($dbName);
        $collection = $db->selectCollection($collectionName);

        $options['projection'] = array('_id' => 1);
        $this->it = $collection->find($filter, $options);

        parent::__construct($this->it);
        $this->rewind();
    }

    public function current()
    {
        $current = parent::current();

        if ($current === null) {
            return null;
        }

        if ($this->collectionName === null) {
            return new $this->objectType($current);
        } else {
            return new $this->objectType($this->collectionName, $current);
        }
    }
}
