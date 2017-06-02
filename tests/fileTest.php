<?php

use photon\db\Connection as DB;
use photon\storage\mongodb\File;
use photon\storage\mongodb\ObjectIterator;

class Flac extends File
{
    const collectionName = 'flac';
}

class FileIteratorTest extends \photon\test\TestCase
{
    public function setup()
    {
        parent::setup();

        $db = DB::get('default');
        $db->drop();
    }

    public function testUnknownFile()
    {
        $this->setExpectedException('photon\storage\mongodb\Exception');
        $file = new Flac(array('filename' => 'lol.flac'));
    }

    public function testCreateAndUpdateFile()
    {
        // Create a new file
        $file = new Flac;
        $stream = $file->getUploadStream('lol.flac');
        fwrite($stream, 'LaaaaLaaaLaaaaaa');
        fclose($stream);
        $file->bitrate = 123;
        $file->encoding = 'le';
        $file->duration = 12458;
        $file->save();

        // Update metadata
        $file = new Flac(array('filename' => 'lol.flac'));
        $file->duration = 1254;
        $file->save();
    }

    public function testCreateAndRenameFile()
    {
        $file = new Flac;
        $stream = $file->getUploadStream('b.flac');
        fwrite($stream, 'TaaaaTaaaDaaaaa');
        fclose($stream);

        $file->filename = 'lol.flac';
        $file->save();

        $file = new Flac(array('filename' => 'lol.flac'));
    }

    public function testMultipleRead()
    {
        $content = 'TaaaaTaaaDaaaaa';

        $file = new Flac;
        $stream = $file->getUploadStream('b.flac');
        fwrite($stream, $content);
        fclose($stream);

        $file = new Flac(array('filename' => 'b.flac'));
        $stream = $file->getDownloadStream();
        $bin = fread($stream, 1500);
        $this->assertEquals($content, $bin);

        $stream = $file->getDownloadStream();
        $bin = fread($stream, 1500);
        $this->assertEquals($content, $bin);
    }

    public function testIteratorFile()
    {
        // Create a new file
        $file = new Flac;
        $stream = $file->getUploadStream('a.flac');
        fwrite($stream, 'LaaaaLaaaLaaaaaa');
        fclose($stream);
        $file->bitrate = 123;
        $file->encoding = 'le';
        $file->duration = 12458;
        $file->save();

        // Create a new file
        $file = new Flac;
        $stream = $file->getUploadStream('b.flac');
        fwrite($stream, 'TaaaaTaaaDaaaaa');
        fclose($stream);

        $it = new ObjectIterator('Flac');
        $nbFileFound = 0;
        foreach($it as $file) {
            $this->assertEquals('Flac', get_class($file));
            $nbFileFound++;


            $file->getFilename();
            $file->getId();
            $file->getMd5();
            $file->getUploadDate();

            $file->rename('willbedelete.txt');

        }
        $this->assertEquals($nbFileFound, 2);

    }
}
