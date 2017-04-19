<?php

use photon\db\Connection as DB;
use photon\storage\mongodb\Object;
use photon\storage\mongodb\ObjectIterator;

class User extends Object
{
    const collectionName = 'users';

    public function initObject()
    {
        $this->ctm = new DateTime;
        $this->activated = false;
    }
}

class ObjectTest extends \photon\test\TestCase
{
    public function setup()
    {
        parent::setup();
        DB::get('default')->users->drop();
    }

    public function testSimpleObject()
    {
        $user = new User;
        $user->name = 'Foo';
        $user->save();

        $reload = new User(array('name' => 'Foo'));
        $reload->delete();
    }

    public function testIteratorObject()
    {
        $user = new User;
        $user->name = 'A';
        $user->save();

        $user = new User;
        $user->name = 'B';
        $user->save();

        $user = new User;
        $user->name = 'C';
        $user->save();

        $filter = array();
        $options = array(
            'sort' => array('ctm' => 1)
        );
        $it = new ObjectIterator('User', $filter, $options);
        $i = 0;
        foreach($it as $user) {
            $this->assertEquals('User', get_class($user));
            $i++;
        }
        $this->assertEquals($i, 3);
    }

    public function testDoNotExistObject()
    {
        $this->setExpectedException('photon\storage\mongodb\Exception');
        $user = new User(array('name' => 'Do not exists'));
    }
}
