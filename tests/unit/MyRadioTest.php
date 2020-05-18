<?php

namespace MyRadio;

use Mockery;
use MyRadio\ServiceAPI\ServiceAPI;

trait MyRadioTest
{
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\MyRadio\Database
     */
    protected $database;

    protected function setUp(): void
    {
        self::overrideDatabase();
    }

    protected function overrideDatabase()
    {
        $this->database = Mockery::mock(\MyRadio\Database::class);
        $rc = new \ReflectionClass(ServiceAPI::class);
        $prop = $rc->getProperty('db');
        $prop->setAccessible(true);
        $prop->setValue($this->database);
    }
}
