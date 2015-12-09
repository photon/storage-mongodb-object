storage-mongodb-object
====================

[![Build Status](https://travis-ci.org/photon/storage-mongodb-object.svg?branch=master)](https://travis-ci.org/photon/storage-mongodb-object)

Micro ORM for MongoDB

Quick start
-----------

1) Add the module in your project

    composer require "photon/storage-mongodb-object:dev-master"

or for a specific version

    composer require "photon/storage-mongodb-object:1.0.0"

2) Define a MongoDB connection in your project configuration

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

3) Create custom object in PHP

4) Enjoy !

