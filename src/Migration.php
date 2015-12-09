<?php

namespace photon\storage\mongodb;
use \photon\db\Connection as DB;

/*
 *  Helper to migrate database from a version to another
 */
class Migration
{
    private $classes = null;

    /*
     *  List all classes in current namespace which extends the Object class
     *  And add the $extra array
     */
    public function scanNamespaceForObject($ns)
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function ($value) {
            if (substr($value, 0, strlen($ns)) !== $ns) {
                return false;
            }

            if (is_subclass_of($value, 'photon\storage\mongodb\Object') === false) {
                return false;
            }

            return true;
        });

        $this->classes = $classes;
    }

    public function addObject($classes)
    {
        $this->classes[] = $classes;
    }

    public function show()
    {
        foreach($this->classes as $class) {
            echo $class . ' = ' . ObjectVersion::get($class) . PHP_EOL;
        }
    }

    /*
     *  Check if some classes need migration
     *  return: true if all classes are up-to-date, else false
     */
    public function check()
    {
        foreach($this->classes as $class) {
            $ret = $this->_check($class);
            if ($ret === false) {
                echo 'Database need migration' . PHP_EOL;
                break;
            }
        }

        return $ret;
    }

    /*
     *  Check if a class need migration
     *  return: true if up-to-date, else false
     */
    public function _check($class)
    {
        $version = ObjectVersion::get($class);
        if ($version !== 0) {
            /*
             *  Execution of the application with database in N-1 and the application code in N
             *  Migration require
             */
            $migrateFunction = 'migrate_' . $version . '_' . ($version + 1);
            if (method_exists($class, $migrateFunction)) {

                return false;
            }
        } else {
            /*
             *  First execution of the application, jump from version 0 to N
             *  No migration needs
             */
            while(true) {
                $version = ObjectVersion::inc($class);

                $migrateFunction = 'migrate_' . $version . '_' . ($version + 1);
                if (method_exists($class, $migrateFunction) === false) {
                    break;
                }
            }
        }

        return true;
    }

    /*
     *  Run migration on all classes
     */
    public function perform()
    {
        foreach($this->classes as $class) {
            $this->_perform($class);
        }
    }

    /*
     *  Run migration on a class
     */
    public function _perform($class)
    {
        $version = ObjectVersion::get($class);
        echo 'Object ' . $class . ' is in version ' . $version . PHP_EOL;

        while (true) {
            $migrateFunction = 'migrate_' . $version . '_' . ($version + 1);
            if (method_exists($class, $migrateFunction) === false) {
                break;
            }

            echo 'Migrate from ' . $version . ' to ' . ($version + 1) . PHP_EOL;
            $ok = forward_static_call(array($class, $migrateFunction));
            if ($ok !== true) {
                echo 'Failed' . PHP_EOL;
                break;
            }

            $version = ObjectVersion::inc($class);
            echo 'Ok' . PHP_EOL;
        }
    }
}

