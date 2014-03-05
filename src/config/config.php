<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Register Types
    |--------------------------------------------------------------------------
    |
    | Doctrine does not support a number of field types. Use this array to map
    | possible field types to their supported alternatives.
    |
    */

    'registerTypes' => array(
        'enum' => 'string',
        'bit' => 'boolean'
    ),

    /*
    |--------------------------------------------------------------------------
    | Field Type Map
    |--------------------------------------------------------------------------
    |
    | Used to convert certain field types and cast them as another. Similar to
    | the registered types, but are not registered by Doctrine and are simply
    | converted as columns are read.
    |
    */

    'fieldTypeMap' => array(
        'guid' => 'string',
        'bigint' => 'integer',
        'littleint' => 'integer',
        'datetimetz' => 'datetime'
    )

);
