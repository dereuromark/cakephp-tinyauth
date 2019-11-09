<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Authorization\AuthorizationService;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
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
		Configure::write('Roles', [
			'user' => ROLE_USER,
			'moderator' => ROLE_MODERATOR,
			'admin' => ROLE_ADMIN
		]);

		$this->componentConfig = [
			'aclFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
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
		$authorization = $this->getMockBuilder(AuthorizationService::class)->disableOriginalConstructor()->getMock();
		$authorization->expects($this->once())
			->method('can')
			->willReturn(true);

		$request = $request->withAttribute('authorization', $authorization);
		$controller = $this->getControllerMock($request);

		$registry = new ComponentRegistry($controller);
		$this->component = new AuthorizationComponent($registry, $this->componentConfig);

		$this->component->authorizeAction();

		$request = $this->component->getController()->getRequest();
		/** @var \Authorization\AuthorizationService $service */
		$service = $request->getAttribute('authorization');

		$this->assertInstanceOf(AuthorizationService::class, $service);
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
