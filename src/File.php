<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;
use \photon\config\Container as Conf;

class File extends Obj
{
    /*
     *  The gridfs name to use for this type offile
     */
    const collectionName = 'fs';

    public function __construct($filter=null)
    {
        parent::__construct($filter);
    }

    static public function getCollectionName()
    {
        return static::collectionName . '.files';
    }

    public function getId()
    {
        return $this->_id;
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

    public function getDownloadStream()
    {
        if ($this->__filter === null) {
            throw new Exception('Can not open download stream on not initialized file');
        }

        $gridfs = $this->__db->selectGridFSBucket(array('bucketName' => static::collectionName));
        return $gridfs->openDownloadStream($this->_id);
    }

    public function getUploadStream($filename, $metadata=array())
    {
        if ($this->__filter !== null) {
            throw new Exception('Can not open upload stream on a already initialized file');
        }

        $id = new \MongoDB\BSON\ObjectId;
        $this->__data['_id'] = $id;
        $this->__filter = array('_id' => $id);

        // Append uploadDate if not provided
        if (isset($metadata['uploadDate']) === false) {
            $metadata['uploadDate'] = new \MongoDB\BSON\UTCDateTime((int)(microtime(true) * 1000));
        }

        // Append contentType if not provided
        if (isset($metadata['contentType']) === false) {
            $metadata['contentType'] = 'application/octet-stream';
        }

        $this->setFields($metadata);

        $gridfs = $this->__db->selectGridFSBucket(array('bucketName' => static::collectionName));
        return $gridfs->openUploadStream($filename, array('_id' => $id));
    }

    public function getBytes()
    {
        $stream = $this->getDownloadStream();
        return stream_get_contents($stream);
    }

    public function rename($newFilename)
    {
        $this->filename = $newFilename;
    }

    public function delete()
    {
        if ($this->__filter === null) {
            throw new Exception('Can not delete a not initialized file');
        }

        $this->preDelete();

        $gridfs = $this->__db->selectGridFSBucket(array('bucketName' => static::collectionName));
        $gridfs->delete($this->_id);
    }

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
}
