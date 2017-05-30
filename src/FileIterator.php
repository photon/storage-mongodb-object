<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

class FileEntry extends \MongoDB\Model\BSONDocument
{
    public $_gridfs;

    public function __construct(\MongoDB\Model\BSONDocument $doc)
    {
        parent::__construct($doc);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getBytes()
    {
        $stream = $this->_gridfs->openDownloadStream($this->_id);
        return stream_get_contents($stream);
    }

    public function rename($newFilename)
    {
        $this->_gridfs->rename($this->_id, $newFilename);
    }

    public function delete()
    {
        $this->_gridfs->delete($this->_id);
    }
}

/*
 *  Create an iterator on file stored in GridFS
 */
class FileIterator extends \IteratorIterator
{
    public $_gridfs;

    public function __construct($gridFsName, $filter=array())
    {
        $databases = Conf::f('databases', array());
        $config = Conf::f('storage-mongodb-object', array());
        $dbName = isset($config['databases']) ? $config['databases'] : 'default';

        $db = DB::get($dbName);
        $this->_gridfs = new \MongoDB\GridFS\Bucket($db->getManager(), $gridFsName);
        $it = $this->_gridfs->find($filter);
        
        parent::__construct($it);
        $this->rewind();        
    }

    public function current()
    {
        $current = new FileEntry(parent::current());
        $current->_gridfs = $this->_gridfs;

        return $current;
    }
}
