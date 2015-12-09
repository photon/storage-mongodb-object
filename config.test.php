<?php

// Enable native support of PHP 64 bits integer
ini_set('mongo.native_long', 1);

return array(
    // Create a list of DB available
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
);
