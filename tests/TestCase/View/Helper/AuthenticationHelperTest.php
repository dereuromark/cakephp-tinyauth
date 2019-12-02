<?php

namespace TinyAuth\Test\TestCase\View\Helper;

use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use TinyAuth\View\Helper\AuthenticationHelper;

class AuthenticationHelperTest extends TestCase {

	/**
	 * @var \TinyAuth\View\Helper\AuthenticationHelper
	 */
	protected $helper;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->config = [
			'allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];

		$view = new View();
		$this->helper = new AuthenticationHelper($view, $this->config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->helper);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testIsPublic() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Users',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1],
		]]);
		$this->helper->getView()->setRequest($request);

		$result = $this->helper->isPublic();
		$this->assertTrue($result);
	}

	/**
	 * Test isPublic method
	 *
	 * @return void
	 */
	public function testIsPublicFail() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Sales',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1],
		]]);
		$this->helper->getView()->setRequest($request);

		$result = $this->helper->isPublic();
		$this->assertFalse($result);
	}

}
