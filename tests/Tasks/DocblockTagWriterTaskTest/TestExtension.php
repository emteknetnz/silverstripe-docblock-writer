<?php

namespace SilverStripe\DocblockWriter\Tests\Tasks\DocblockTagWriterTaskTest;

use SilverStripe\Core\Extension;

class TestExtension extends Extension
{
    private static $has_one = [
        'SomeHasOne' => TestObjectB::class,
    ];
}
