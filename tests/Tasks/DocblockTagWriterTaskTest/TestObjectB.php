<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;

class TestObjectB extends DataObject implements TestOnly
{
    private static $belongs_many_many = [
        'SomeManyManys' => TestObjectA::class,
    ];
}
