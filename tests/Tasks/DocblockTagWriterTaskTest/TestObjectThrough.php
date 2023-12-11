<?php

namespace emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class TestObjectThrough extends DataObject implements TestOnly
{
    private static $table_name = 'DocblockTagWriterTaskTest_TestObjectThrough';

    private static array $has_one = [
        'TestObjectA' => TestObjectA::class,
        'TestObjectB' => TestObjectB::class,
    ];
}
