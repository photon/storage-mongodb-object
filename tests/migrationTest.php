<?php

use photon\db\Connection as DB;
use photon\storage\mongodb\Obj;
use photon\storage\mongodb\ObjectIterator;
use photon\storage\mongodb\ObjectVersion;
use photon\storage\mongodb\Migration;

class ObjA extends Obj
{
    const collectionName = 'ObjA';
}

class ObjB extends Obj
{
    const collectionName = 'ObjB';

    /*
     *  Database migration function
     *  Version 1 to 2
     */
    public static function migrate_1_2()
    {
        $it = new ObjectIterator('ObjB');
        foreach ($it as $objB) {
            // Migration code here
        }

        return true; // success
    }
}

class ObjC extends Obj
{
    const collectionName = 'ObjC';

    /*
     *  Database migration function
     *  Version 1 to 2
     */
    public static function migrate_1_2()
    {
        // throw exception for test
        throw new Exception;
    }
}

class MigrationTest extends \photon\test\TestCase
{
    public function setup()
    {
        parent::setup();

        $versions = new ObjectVersion;
        $versions->resetAll();
    }


    public function testMigrationScanNs()
    {
        $migration = new Migration;
        $migration->scanNamespaceForObject("\\");
    }

    public function testMigrationInitAndCheck()
    {
        // First time the App run, the object ObjA go in version 1
        $migration = new Migration;
        $migration->addObject('ObjA');
        $migration->show();
        $migration->check();
        $version = ObjectVersion::get('ObjA');
        $this->assertEquals($version, 1);

        // First time the App run, the object ObjB go in version 2
        $migration = new Migration;
        $migration->addObject('ObjB');
        $migration->show();
        $migration->check();
        $version = ObjectVersion::get('ObjB');
        $this->assertEquals($version, 2);
    }

    public function testMigrationPerform()
    {
        // Simulate a previous run with object in version 1
        ObjectVersion::inc('ObjC');

        $migration = new Migration;
        $migration->addObject('ObjC');
        $migration->show();
        $migration->check();

        $this->setExpectedException('Exception');
        $migration->perform();
    }
}
