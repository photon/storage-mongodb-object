<?php

namespace photon\storage\mongodb;
use photon\utils\mongodb\Counter;

/*
 *  Object counter to track current version of each object class
 *  It's use to detect when a class (i.e. a collection) need a database migration
 */
class ObjectVersion extends Counter {
    const collection = 'objectVersion';
}
