<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

class FileEntry extends \MongoDB\Model\BSONDocument
{
    public $_db;
    public $_gridfs;
    public $_collectionFiles;
    public $_collectionChunks;

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

    public function getMd5()
    {
        return $this->md5;
    }

    public function getUploadDate()
    {
        return $this->uploadDate;
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

    public function updateMetadata($metadata)
    {
        $collection = $this->_db->selectCollection($this->_collectionFiles);
        $collection->findOneAndUpdate(
            array('_id' => $this->_id),
            $metadata,
            array('upsert' => false)
        );
    }
}

/*
 *  Create an iterator on file stored in GridFS
 */
class FileIterator extends \IteratorIterator
{
    public $_db;
    public $_gridfs;
    public $_gridfsname;

    public function __construct($gridfsname, $filter=array())
    {
        $databases = Conf::f('databases', array());
        $config = Conf::f('storage-mongodb-object', array());
        $dbName = isset($config['databases']) ? $config['databases'] : 'default';

        $this->_gridfsname = $gridfsname;
        $this->_db = DB::get($dbName);
        $this->_gridfs = new \MongoDB\GridFS\Bucket($this->_db->getManager(), $gridfsname);
        $it = $this->_gridfs->find($filter);
        
        parent::__construct($it);
        $this->rewind();        
    }

    public function current()
    {
        $current = new FileEntry(parent::current());
        $current->_db = $this->_db;
        $current->_gridfs = $this->_gridfs;
        $current->_collectionFiles = $this->_gridfsname . '.files';
        $current->_collectionChunks = $this->_gridfsname . '.chunks';

        return $current;
    }
}
