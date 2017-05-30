<?php

use photon\db\Connection as DB;
use photon\storage\mongodb\FileIterator;

class FileIteratorTest extends \photon\test\TestCase
{
    public function setup()
    {
        parent::setup();

        $db = DB::get('default');
        $gridfs = new \MongoDB\GridFS\Bucket($db->getManager(), 'gridfsname');
        $gridfs->drop();
    }

    public function testIteratorFile()
    {
        $db = DB::get('default');
        $gridfs = new \MongoDB\GridFS\Bucket($db->getManager(), 'gridfsname');

        $data = array(
            'memo_a.txt' => 'bin data very important',
            'memo_b.bin' => '12345648913461320564846',
            'memo_c.csv' => 'a,c,v,f,f,g,bv,fr,r,f,f'
        );
        foreach($data as $filename => $content) {
            $stream = $gridfs->openUploadStream($filename);
            fwrite($stream, $content);
            fclose($stream);
        }

        $i = 0;
        $it = new FileIterator('gridfsname');
        foreach ($it as $file) {
            $filename = $file->getFilename();
            $content = $file->getBytes();

            $this->assertEquals($content, $data[$filename]);
            $i++;

            $file->rename('willbedelete.txt');
            $file->delete();
        }

        $this->assertEquals($i, 3);
    }
}
