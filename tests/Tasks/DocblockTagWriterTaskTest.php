<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectA;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectB;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestObjectThrough;
use SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest\TestExtension;
use SilverStripe\DocblockWriter\Tasks\DocblockTagWriterTask;
use PHPUnit\Util\Test;
use ReflectionObject;

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
        $reflector = new ReflectionObject($task);
        $method = $reflector->getMethod('getProcessableFiles');
        $method->setAccessible(true);
        $actual = $method->invoke($task, $pathFilter);
        $this->assertSame($expected, $actual);
    }

    public function testCreateNewDocblock()
    {
        $task = new DocblockTagWriterTask();
        $reflector = new ReflectionObject($task);
        $method = $reflector->getMethod('createnewDocblock');
        $method->setAccessible(true);
        $pathFilter = $this->getPathFilter();
        // TestObjectA - has_one, has_many, many_many, many_many_through
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
        $actual = $method->invoke($task, $className, $contents, $path);
        $this->assertSame($expected, $actual);
        // TestObjectB - belongs_many_many, existing docblock with deprecated tag
        $className = TestObjectB::class;
        $path = "$pathFilter/TestObjectB.php";
        $contents = file_get_contents($path);
        $expected = <<<EOT
        /**
         * This is my existing docblock. Note this class is NOT deprecated it is for unit testing
         *
         * @method SilverStripe\ORM\ManyManyList<TestObjectA> SomeManyManys()
         *
         * @deprecated This is for testing that the deprecated tag goes below added method tags
         */
        EOT;
        $actual = $method->invoke($task, $className, $contents, $path);
        $this->assertSame($expected, $actual);
    }

    private function getPathFilter(): string
    {
        return str_replace('.php', '', __FILE__);
    }
}
