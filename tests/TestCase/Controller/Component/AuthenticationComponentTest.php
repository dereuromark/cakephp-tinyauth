<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TinyAuth\Controller\Component\AuthenticationComponent;

class AuthenticationComponentTest extends TestCase {

	/**
	 * @var \TinyAuth\Controller\Component\AuthenticationComponent
	 */
	protected $component;

	/**
	 * @var array
	 */
	protected $componentConfig = [];

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->componentConfig = [
			'allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];
	}

	/**
	 * @return void
	 */
	public function testValid() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Users',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$config = [];
		$this->component->initialize($config);

		$event = new Event('Controller.startup', $controller);
		$response = $this->component->startup($event);
		$this->assertNull($response);
	}

	/**
	 * @return void
	 */
	public function testIsPublic() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Users',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicFail() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Sales',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [1],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicAllowNonPrefixed() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Foos',
			'action' => 'view',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowNonPrefixed' => true] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicAllowNonPrefixedFail() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'Foo',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowNonPrefixed' => true] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicAllowPrefixed() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'FooBar',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowPrefixes' => 'FooBar'] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicAllowPrefixedFail() {
		$request = new ServerRequest([
'params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'Foo',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowPrefixes' => 'FooBar'] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request
	 * @return \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject
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
