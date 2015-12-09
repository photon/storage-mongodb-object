<?php

use photon\db\Connection as DB;
use photon\storage\mongodb\FileIterator;

class FileIteratorTest extends \photon\test\TestCase
{
    public function testIteratorFile()
    {
        $db = DB::get('default');
        $gridfs = $db->getGridFS('gridfsname');

        $data = array(
            'memo_a.txt' => 'bin data very important',
            'memo_b.bin' => '12345648913461320564846',
            'memo_c.csv' => 'a,c,v,f,f,g,bv,fr,r,f,f'
        );
        foreach($data as $filename => $content) {
            $gridfs->storeBytes($content, array('filename' => $filename));
        }

        $i = 0;
        $it = new FileIterator('gridfsname');
        foreach ($it as $file) {
            $filename = $file->getFilename();
            $content = $file->getBytes();
            $this->assertEquals($content, $data[$filename]);
            $i++;
        }
        $this->assertEquals($i, 3);
    }
}
