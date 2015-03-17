<?php
use \MyRadio\MyRadio\CoreUtils;

/**
 * @backupStaticAttributes enabled
 */
class CoreUtilsTest extends PHPUnit_Framework_TestCase {

	public function testIsValidController()
	{
		$this->assertTrue(CoreUtils::isValidController('MyRadio', 'default'));
		$this->assertTrue(CoreUtils::isValidController('MyRadio'));
		$this->assertFalse(CoreUtils::isValidController('MyRadio/../'));
		$this->assertFalse(CoreUtils::isValidController('iDoNotExistSorry'));
	}

	public function testHappyTime()
	{
		$this->assertEquals('01/01/1970 11:06', CoreUtils::happyTime(40000));
		$this->assertEquals('01/01/1970', CoreUtils::happyTime(40000, false));
		$this->assertEquals('11:06', CoreUtils::happyTime(40000, true, false));
	}

	public function testIntToTime()
	{
		$this->assertEquals('24:01:40', CoreUtils::intToTime(86500));
		$this->assertEquals('12:34:56', CoreUtils::intToTime(45296));
	}

	public function testGetTimestamp()
	{
		$this->assertEquals('1970-01-01 00:00:30+00', CoreUtils::getTimestamp(30));
	}

	public function testGetYearAndWeekNo()
	{
		$this->assertEquals([1970, 1], CoreUtils::getYearAndWeekNo(0));
		$this->assertEquals([2014, 51], CoreUtils::getYearAndWeekNo(1418939395)); //18th Dec 2014
		$this->assertEquals([2011, 52], CoreUtils::getYearAndWeekNo(1325454595)); //1st Jan 2012
	}

	public function testGetAcademicYear()
	{
		$container = CoreUtils::getContainer();
		$container['database'] = function() {
			$db = \Mockery::mock('\MyRadio\Database');
			$db->shouldDeferMissing()->shouldReceive('fetchColumn')->andReturn(['2015-03-09']);
			return $db;
		};
		CoreUtils::registerContainer($container);

		$this->assertEquals(2015, CoreUtils::getAcademicYear(1426630418)); //17th March 2015
		$this->assertEquals(2015, CoreUtils::getAcademicYear(1420235395)); //2nd Jan 2015
		$this->assertEquals(2015, CoreUtils::getAcademicYear(1451684995)); //1st Jan 2016
	}

	public function testMakeInterval()
	{
		$this->assertEquals('60 seconds', CoreUtils::makeInterval(0, 60));
		$this->assertEquals('-60 seconds', CoreUtils::makeInterval(60, 0));
	}

}
