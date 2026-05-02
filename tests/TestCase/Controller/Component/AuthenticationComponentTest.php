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
	protected array $componentConfig = [];

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
			],
		]);
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
			],
		]);
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
			],
		]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * Regression: a rule scoped to a plugin must not match a request without one.
	 *
	 * `Extras.Offers = "!superPrivate", *` defines a plugin-scoped rule.
	 * A request to non-plugin `Offers::view` previously matched it because
	 * `_isPublic()` short-circuited the plugin guard when the request had none.
	 *
	 * @return void
	 */
	public function testIsPublicDoesNotMatchPluginRuleForNonPluginRequest() {
		$request = new ServerRequest([
			'params' => [
				'controller' => 'Offers',
				'action' => 'view',
				'plugin' => null,
				'_ext' => null,
				'pass' => [],
			],
		]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * Regression: a rule scoped to a prefix must not match a request without one.
	 *
	 * `Admin/Users = index` is prefix-scoped to `Admin`. A non-prefix request
	 * to `Users::index` is already covered by the unprefixed `Users` rule, but
	 * a non-prefix request to `Users::view` (which is allowed unprefixed too)
	 * must not pull in the Admin-scoped rule. We exercise the inverse path by
	 * requesting an action that is only allowed under the Admin prefix to make
	 * sure the prefix-scoped rule does not leak to non-prefix requests.
	 *
	 * @return void
	 */
	public function testIsPublicDoesNotMatchPrefixRuleForNonPrefixRequest() {
		$request = new ServerRequest([
			'params' => [
				'controller' => 'MyTest',
				'action' => 'myPublic',
				'plugin' => null,
				'prefix' => null,
				'_ext' => null,
				'pass' => [],
			],
		]);
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
			],
		]);
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
			],
		]);
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
			],
		]);
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
			],
		]);
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
	protected function getControllerMock(ServerRequest $request): Controller {
		$controller = $this->getMockBuilder(Controller::class)
			->setConstructorArgs([$request])
			->onlyMethods(['isAction'])
			->getMock();

		$controller->method('isAction')->willReturn(true);

		return $controller;
	}

}
