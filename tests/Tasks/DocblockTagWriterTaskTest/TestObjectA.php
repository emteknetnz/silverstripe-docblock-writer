<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;

class TestObjectA extends DataObject implements TestOnly
{
    private static $table_name = 'DocblockTagWriterTaskTest_TestObjectA';

    private static $has_one = [
        'MyHasOne' => TestObjectB::class,
    ];

    private static $has_many = [
        'MyHasManys' => TestObjectB::class . '.Lorem',
    ];

    private static $many_many = [
        'MyManyManys' => TestObjectB::class,
        'MyManyManyThroughs' => [
            'through' => TestObjectThrough::class,
            'from' => 'TestObjectA',
            'to' => 'TestObjectB',
        ],
    ];
}
