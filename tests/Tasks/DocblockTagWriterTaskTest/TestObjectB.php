<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;

/**
 * This is my existing docblock. Note this class is NOT deprecated it is for unit testing
 *
 * @deprecated This is for testing that the deprecated tag goes below added method tags
 */
class TestObjectB extends DataObject implements TestOnly
{
    private static $table_name = 'DocblockTagWriterTaskTest_TestObjectB';

    private static $belongs_many_many = [
        'SomeManyManys' => TestObjectA::class,
    ];
}
