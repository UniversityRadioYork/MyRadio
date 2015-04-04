<?php
use \MyRadio\MyRadio\CoreUtils;

class CoreUtilsTest extends MyRadio_TestCase {

	public function testIsValidController()
	{
		$this->assertTrue(CoreUtils::isValidController('MyRadio', 'default'));
		$this->assertTrue(CoreUtils::isValidController('MyRadio'));
		$this->assertFalse(CoreUtils::isValidController('MyRadio/../'));
		$this->assertFalse(CoreUtils::isValidController('iDoNotExistSorry'));
	}

	public function testGetTemplateObject()
	{
		$this->assertInstanceOf('\MyRadio\Iface\TemplateEngine', CoreUtils::getTemplateObject());
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
		$this->assertEquals(gmdate('Y-m-d H:i:s+00'), CoreUtils::getTimestamp());
	}

	public function testGetYearAndWeekNo()
	{
		$this->assertEquals([1970, 1], CoreUtils::getYearAndWeekNo(0));
		$this->assertEquals([2014, 51], CoreUtils::getYearAndWeekNo(1418939395)); //18th Dec 2014
		$this->assertEquals([2011, 52], CoreUtils::getYearAndWeekNo(1325454595)); //1st Jan 2012
	}

	public function testGetAcademicYear()
	{
		self::wireMockDatabase(function() {
			$db = \Mockery::mock('\MyRadio\Database');
			$db->shouldDeferMissing()->shouldReceive('fetchColumn')->andReturn(['2015-03-09']);
			return $db;
		});

		$this->assertEquals(2015, CoreUtils::getAcademicYear(1426630418)); //17th March 2015
		$this->assertEquals(2015, CoreUtils::getAcademicYear(1420235395)); //2nd Jan 2015
		$this->assertEquals(2015, CoreUtils::getAcademicYear(1451684995)); //1st Jan 2016
	}

	public function testMakeInterval()
	{
		$this->assertEquals('60 seconds', CoreUtils::makeInterval(0, 60));
		$this->assertEquals('-60 seconds', CoreUtils::makeInterval(60, 0));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testDataToJSON()
	{
		$this->assertEquals('null', CoreUtils::dataToJSON(null));
		$this->assertEquals('[]', CoreUtils::dataToJSON([]));
		$this->assertEquals('{"foo":"bar"}', CoreUtils::dataToJSON(["foo"=>"bar"]));

		\MyRadio\MyRadioError::$php_errorlist = ['foo went wrong'];
		CoreUtils::getContainer()['config']->display_errors = true;
		$this->assertEquals('{"foo":"bar","myradio_errors":["foo went wrong"]}', CoreUtils::dataToJSON(["foo"=>"bar"]));
	}

	public function testMakeURL()
	{
		CoreUtils::getContainer()['config']->base_url = '//example.com/myradio/';
		CoreUtils::getContainer()['config']->rewrite_url = true;
		$this->assertEquals('//example.com/myradio/foo/bar/', CoreUtils::makeURL('foo', 'bar'));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', CoreUtils::makeURL('foo', 'bar', ['meow' => 'cat']));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', CoreUtils::makeURL('foo', 'bar', 'meow=cat'));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', CoreUtils::makeURL('foo', 'bar', '?meow=cat'));

		CoreUtils::getContainer()['config']->rewrite_url = false;
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar', CoreUtils::makeURL('foo', 'bar'));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', CoreUtils::makeURL('foo', 'bar', ['meow' => 'cat']));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', CoreUtils::makeURL('foo', 'bar', 'meow=cat'));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', CoreUtils::makeURL('foo', 'bar', '?meow=cat'));
	}

	public function testRequirePermissionAuto()
	{
		
	}

}
