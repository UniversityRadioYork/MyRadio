<?php
use \MyRadio\MyRadio\CoreUtils;

use \Aura\Di\Container;
use \Aura\Di\Factory;

/**
 * @runTestsInSeparateProcesses
 */
class CoreUtilsTest extends PHPUnit_Framework_TestCase {

	public function getObject()
	{
		$config = new \MyRadio\Config;
		$config->display_errors = true;

		$utils = new CoreUtils;
		$utils->setConfig($config);

		return $utils;
	}

	public function testIsValidController()
	{	
		$utils = $this->getObject();
		$this->assertTrue($utils->isValidController('MyRadio', 'default'));
		$this->assertTrue($utils->isValidController('MyRadio'));
		$this->assertFalse($utils->isValidController('MyRadio/../'));
		$this->assertFalse($utils->isValidController('iDoNotExistSorry'));
	}

	public function testHappyTime()
	{
		$utils = $this->getObject();
		$this->assertEquals('01/01/1970 11:06', $utils->happyTime(40000));
		$this->assertEquals('01/01/1970', $utils->happyTime(40000, false));
		$this->assertEquals('11:06', $utils->happyTime(40000, true, false));
	}

	public function testIntToTime()
	{
		$utils = $this->getObject();
		$this->assertEquals('24:01:40', $utils->intToTime(86500));
		$this->assertEquals('12:34:56', $utils->intToTime(45296));
	}

	public function testGetTimestamp()
	{
		$utils = $this->getObject();
		$this->assertEquals('1970-01-01 00:00:30+00', $utils->getTimestamp(30));
		$this->assertEquals(gmdate('Y-m-d H:i:s+00'), $utils->getTimestamp());
	}

	public function testGetYearAndWeekNo()
	{
		$utils = $this->getObject();
		$this->assertEquals([1970, 1], $utils->getYearAndWeekNo(0));
		$this->assertEquals([2014, 51], $utils->getYearAndWeekNo(1418939395)); //18th Dec 2014
		$this->assertEquals([2011, 52], $utils->getYearAndWeekNo(1325454595)); //1st Jan 2012
	}

	public function testGetAcademicYear()
	{
		$utils = $this->getObject();

		$db = \Mockery::mock('\MyRadio\Database');
		$db->shouldDeferMissing()->shouldReceive('fetchColumn')->andReturn(['2015-03-09']);
		$utils->setDatabase($db);

		$this->assertEquals(2015, $utils->getAcademicYear(1426630418)); //17th March 2015
		$this->assertEquals(2015, $utils->getAcademicYear(1420235395)); //2nd Jan 2015
		$this->assertEquals(2015, $utils->getAcademicYear(1451684995)); //1st Jan 2016
	}

	public function testMakeInterval()
	{
		$utils = $this->getObject();
		$this->assertEquals('60 seconds', $utils->makeInterval(0, 60));
		$this->assertEquals('-60 seconds', $utils->makeInterval(60, 0));
	}

	public function testDataToJSON()
	{
		$utils = $this->getObject();
		$this->assertEquals('null', $utils->dataToJSON(null));
		$this->assertEquals('[]', $utils->dataToJSON([]));
		$this->assertEquals('{"foo":"bar"}', $utils->dataToJSON(["foo"=>"bar"]));

		\MyRadio\MyRadioError::$php_errorlist = ['foo went wrong'];
		$this->assertEquals('{"foo":"bar","myradio_errors":["foo went wrong"]}', $utils->dataToJSON(["foo"=>"bar"]));
	}

	public function testMakeURL()
	{
		$utils = $this->getObject();

		$config = new MyRadio\Config;
		$config->base_url = '//example.com/myradio/';
		$config->rewrite_url = true;
		$utils->setConfig($config);

		$this->assertEquals('//example.com/myradio/foo/bar/', $utils->makeURL('foo', 'bar'));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', $utils->makeURL('foo', 'bar', ['meow' => 'cat']));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', $utils->makeURL('foo', 'bar', 'meow=cat'));
		$this->assertEquals('//example.com/myradio/foo/bar/?meow=cat', $utils->makeURL('foo', 'bar', '?meow=cat'));

		$config->rewrite_url = false;
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar', $utils->makeURL('foo', 'bar'));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', $utils->makeURL('foo', 'bar', ['meow' => 'cat']));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', $utils->makeURL('foo', 'bar', 'meow=cat'));
		$this->assertEquals('//example.com/myradio/?module=foo&action=bar&meow=cat', $utils->makeURL('foo', 'bar', '?meow=cat'));
	}

	public function testRequirePermissionAuto()
	{
		$utils = $this->getObject();

		define('AUTH_NOLOGIN', -1);

		$db = \Mockery::mock('\MyRadio\Database');
		$db->shouldDeferMissing()
			//setUpAuth
			->shouldReceive('fetchAll')
			->andReturn([])
			//get action permissions for first test (anonymous access)
			->shouldReceive('fetchColumn')->once()
			->andReturn([-1])
			//get action permissions for second test (login required)
			->shouldReceive('fetchColumn')->once()
			->andReturn([null])
			//get action permissions for third test (no permissions defined)
			->shouldReceive('fetchColumn')->once()
			->andReturn([])
			//get action permissions for fourth test (logged in user granted)
			->shouldReceive('fetchColumn')->once()
			->andReturn([null]);
		$utils->setDatabase($db);

		$user = \Mockery::mock('\MyRadio\ServiceAPI\User');
		$user->shouldDeferMissing()
			//get action permissions for fourth test (logged in user granted)
			->shouldReceive('hasAuth')->once()
			->andReturn(true);
		$factory = \Mockery::mock('\MyRadio\MyRadio\MyRadioServiceFactory');
		$factory->shouldDeferMissing()
			->shouldReceive('getInstanceOf')
			->andReturn($user);
		$utils->setServiceFactory($factory);
		
		try {
			$utils->requirePermissionAuto('foo', 'bar');
		} catch (\MyRadio\MyRadioException $e) {
			$this->fail('Should have permission to anonymously access foo/bar but ' . $e->getMessage());
		}

		try {
			$utils->requirePermissionAuto('foo', 'bar');
			$this->fail('Should not have permission to anonymously access foo/bar');
		} catch (\MyRadio\MyRadioException $e) {}

		try {
			$utils->requirePermissionAuto('foo', 'bar');
			$this->fail('Should not be able to access foo/bar without defined permissions');
		} catch (\MyRadio\MyRadioException $e) {}

		$dummySession = new DummySession();
		$dummySession['memberid'] = 1;
		$utils->setSession($dummySession);
		$this->assertTrue($utils->requirePermissionAuto('foo', 'bar'));
	}

}
