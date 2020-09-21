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

    public function testGetAll()
    {
        $this->database->shouldReceive('fetchAll')
            ->andReturn([
                [
                    'show_subtype_id' => 1,
                    'name' => 'Test1',
                    'class' => 'test',
                    'description' => 'Test'
                ],
                [
                    'show_subtype_id' => 2,
                    'name' => 'Test2',
                    'class' => 'test',
                    'description' => 'Test'
                ]
            ]);

        $test = MyRadio_ShowSubtype::getAll();

        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($i+1, $test[$i]['id']);
            $this->assertEquals('Test'.($i+1), $test[$i]['name']);
            $this->assertEquals('test', $test[$i]['class']);
        }
    }
}
