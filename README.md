storage-mongodb-object
====================

[![Build Status](https://travis-ci.org/photon/storage-mongodb-object.svg?branch=master)](https://travis-ci.org/photon/storage-mongodb-object)

Micro ORM for MongoDB

PHP Versions
------------

- 5.6, 7.0 and 7.1 are supported and tested under travis
- Use ext-mongodb and mongodb/mongodb. Do not works anymore with legacy ext-mongo


Quick start
-----------

1) Add the module in your project

You need to have composer available in your system

    composer require "photon/storage-mongodb-object:dev-master"

or for a specific version

    composer require "photon/storage-mongodb-object:^3.0"

2) Define a database

Define a MongoDB connection in your project configuration

    'databases' => array(
        'default' => array(
            'engine' => '\photon\db\MongoDB',
            'server' => 'mongodb://localhost:27017/',
            'database' => 'orm',
            'options' => array(
                'connect' => true,
            ),
        ),
    ),

3) Create custom object

For exemple a class to store user informations

    class User extends \photon\storage\mongodb\Object
    {
        const collectionName = 'users';

        public function initObject()
        {
            $this->ctm = new DateTime;
            $this->activated = false;
        }
    }

4) Use it

Use you object in your PHP code

    $user = new User;
    $user->name = 'Foo';
    $user->save();

5) Enjoy !

