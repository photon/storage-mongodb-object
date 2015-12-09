<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

/*
 *  Create an iterator on file stored in GridFS
 */
class FileIterator extends \MongoGridFSCursor
{
    public function __construct($gridFsName, $filter=array())
    {
        $databases = Conf::f('databases', array());
        $config = Conf::f('storage-mongodb-object', array());
        $dbName = isset($config['databases']) ? $config['databases'] : 'default';
        if (!isset($databases[$dbName])) {
            throw new \photon\db\UndefinedConnection(sprintf('The connection "%s" is not defined in the configuration.', $dbName));
        }
        
        $class = class_exists('\MongoClient') ? '\MongoClient' : '\Mongo';
        $conn = new $class($databases[$dbName]['server'], $databases[$dbName]['options']);
        $db = $conn->selectDB($databases[$dbName]['database']);
        $gridfs = $db->getGridFS($gridFsName);
        $ns = $databases[$dbName]['database'] . '.' . $gridFsName . '.files';

        $fields = array();
        parent::__construct($gridfs, $conn, $ns, $filter, $fields);
    }
}
