<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectA;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectB;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectThrough;
use SilverStripe\DockblockWriter\Tasks\DocblockTagWriterTask;

class DocblockTagWriterTaskTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestObjectA::class,
        TestObjectB::class,
        TestObjectThrough::class,
    ];

    public function testSomething()
    {
        $task = new DocblockTagWriterTask();
        $pathFilter = basename(__FILE__);
        var_dump($pathFilter);
        // TODO write some tests
        $this->assertTrue(true);
    }
}
