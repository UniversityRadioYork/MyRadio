<?php


namespace MyRadio\ServiceAPI;

use MyRadio\MyRadioTest;
use PHPUnit\Framework\TestCase;

class MyRadio_ShowSubtypeTest extends TestCase
{
    use MyRadioTest;

    public function testFactory()
    {
        $this->database->shouldReceive('fetchOne')
            ->andReturn([
                'show_subtype_id' => 1,
                'name' => 'Test',
                'class' => 'test',
                'description' => 'Test'
            ]);

        /** @var MyRadio_ShowSubtype $test */
        $test = MyRadio_ShowSubtype::getInstance(1);

        $this->assertEquals(1, $test->getID());
        $this->assertEquals('Test', $test->getName());
        $this->assertEquals('test', $test->getClass());
    }
}