<?php

namespace emteknetnz\DocblockWriter\Tests\Tasks;

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

    public function testGetProcessableFiles()
    {
        $task = new DocblockTagWriterTask();
        $pathFilter = __DIR__ . str_replace('.php', '', __FILE__);
        $expected = [];
        $actual = $task->getProcessableFiles($pathFilter);
        $this->assertSame($expected, $actual);
    }
}
