<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class TestObjectThrough extends DataObject implements TestOnly
{
    private static array $has_one = [
        'TestObjectA' => TestObjectA::class,
        'TestObjectB' => TestObjectB::class,
    ];
}
