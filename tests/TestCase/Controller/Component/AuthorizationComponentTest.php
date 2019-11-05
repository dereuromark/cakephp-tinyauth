<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TinyAuth\Controller\Component\AuthorizationComponent;

class AuthorizationComponentTest extends TestCase {

	/**
	 * @var \TinyAuth\Controller\Component\AuthorizationComponent
	 */
	protected $component;

	/**
	 * @var array
	 */
	protected $componentConfig = [];

	/**
	 * @return void
	 */
	public function setUp() {
		$this->componentConfig = [
			'allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];
	}

	/**
	 * @return void
	 */
	public function testValid() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Users',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1]
		]]);
		$controller = $this->getControllerMock($request);

		$registry = new ComponentRegistry($controller);
		$this->component = new AuthorizationComponent($registry, $this->componentConfig);

		$config = [];
		$this->component->initialize($config);

		$event = new Event('Controller.startup', $controller);
		$response = $this->component->startup($event);
		$this->assertNull($response);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request
	 * @return \Cake\Controller\Controller|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getControllerMock(ServerRequest $request) {
		$controller = $this->getMockBuilder(Controller::class)
			->setConstructorArgs([$request])
			->setMethods(['isAction'])
			->getMock();

		$controller->expects($this->any())->method('isAction')->willReturn(true);

		return $controller;
	}

}
