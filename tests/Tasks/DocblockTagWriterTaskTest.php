<?php

namespace emteknetnz\DocblockWriter\Tests\Tasks;

use SilverStripe\Dev\SapphireTest;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectA;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectB;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectThrough;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestExtension;
use emteknetnz\DocblockWriter\Tasks\DocblockTagWriterTask;

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
        $pathFilter = str_replace('.php', '', __FILE__);
        $expected = [
            [
                'path' => $pathFilter . '/TestObjectA.php',
                'dataClass' => TestObjectA::class,
            ],
            [
                'path' => $pathFilter . '/TestObjectB.php',
                'dataClass' => TestObjectB::class,
            ],
            [
                'path' => $pathFilter . '/TestObjectThrough.php',
                'dataClass' => TestObjectThrough::class,
            ],
            [
                'path' => $pathFilter . '/TestExtension.php',
                'dataClass' => TestExtension::class,
            ],
        ];
        $actual = $task->getProcessableFiles($pathFilter);
        $this->assertSame($expected, $actual);
    }
}
