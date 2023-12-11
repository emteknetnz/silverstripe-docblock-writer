<?php

namespace emteknetnz\DocblockWriter\Tests\Tasks;

use SilverStripe\Dev\SapphireTest;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectA;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectB;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectThrough;
use emteknetnz\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestExtension;
use emteknetnz\DocblockWriter\Tasks\DocblockTagWriterTask;
use PHPUnit\Util\Test;

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
        $pathFilter = $this->getPathFilter();
        $expected = [
            [
                'path' => "$pathFilter/TestObjectA.php",
                'className' => TestObjectA::class,
            ],
            [
                'path' => "$pathFilter/TestObjectB.php",
                'className' => TestObjectB::class,
            ],
            [
                'path' => "$pathFilter/TestObjectThrough.php",
                'className' => TestObjectThrough::class,
            ],
            [
                'path' => "$pathFilter/TestExtension.php",
                'className' => TestExtension::class,
            ],
        ];
        $actual = $task->getProcessableFiles($pathFilter);
        $this->assertSame($expected, $actual);
    }

    public function testCreateNewDocblock()
    {
        $task = new DocblockTagWriterTask();
        $pathFilter = $this->getPathFilter();
        // TestObjectA
        $className = TestObjectA::class;
        $path = "$pathFilter/TestObjectA.php";
        $contents = file_get_contents($path);
        $expected = <<<EOT
        /**
         * @method SilverStripe\ORM\HasManyList<TestObjectB> MyHasManys()
         * @method TestObjectB MyHasOne()
         * @method SilverStripe\ORM\ManyManyList<TestObjectB> MyManyManyThroughs()
         * @method SilverStripe\ORM\ManyManyList<TestObjectB> MyManyManys()
         */
        EOT;
        $actual = $task->createNewDocblock($className, $contents, $path);
        $this->assertSame($expected, $actual);
        // TestObjectB
        $className = TestObjectB::class;
        $path = "$pathFilter/TestObjectB.php";
        $contents = file_get_contents($path);
        $expected = <<<EOT
        /**
         * @method SilverStripe\ORM\ManyManyList<TestObjectA> SomeManyManys()
         */
        EOT;
        $actual = $task->createNewDocblock($className, $contents, $path);
        $this->assertSame($expected, $actual);
    }

    private function getPathFilter(): string
    {
        return str_replace('.php', '', __FILE__);
    }
}
