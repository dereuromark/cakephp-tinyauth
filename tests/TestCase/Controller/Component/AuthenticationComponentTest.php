<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ReflectionMethod;
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
		$request = new ServerRequest(['params' => [
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
		$request = new ServerRequest(['params' => [
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
		$request = new ServerRequest(['params' => [
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
		$request = new ServerRequest(['params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'foo',
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
		$request = new ServerRequest(['params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'foo_bar',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowPrefixes' => 'foo_bar'] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsPublicAllowPrefixedFail() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Foos',
			'action' => 'view',
			'prefix' => 'Foo',
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, ['allowPrefixes' => 'foo_bar'] + $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * A wildcard (`*`) allow rule must keep unauthenticated access open even for
	 * actions that do not exist on the controller. Otherwise an unknown action
	 * under a fully public controller is treated as protected and unauthenticated
	 * users get redirected to login instead of receiving the MissingActionException
	 * (404) that the action's absence should produce.
	 *
	 * @see https://github.com/dereuromark/cakephp-tinyauth/issues/173
	 *
	 * @return void
	 */
	public function testPrepareAuthenticationWildcardAllowsUnknownAction() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Offers',
			'action' => 'thisDoesNotExist',
			'plugin' => 'Extras',
			'prefix' => null,
			'_ext' => null,
			'pass' => [],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$method = new ReflectionMethod($this->component, '_prepareAuthentication');
		$method->setAccessible(true);
		$method->invoke($this->component);

		$this->assertContains('thisDoesNotExist', $this->component->getUnauthenticatedActions());
	}

	/**
	 * A wildcard allow rule with an explicit deny must still protect the denied
	 * action, even though the requested action does not exist on the controller.
	 *
	 * @return void
	 */
	public function testPrepareAuthenticationWildcardStillHonorsDeny() {
		$request = new ServerRequest(['params' => [
			'controller' => 'Offers',
			'action' => 'superPrivate',
			'plugin' => 'Extras',
			'prefix' => null,
			'_ext' => null,
			'pass' => [],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$method = new ReflectionMethod($this->component, '_prepareAuthentication');
		$method->setAccessible(true);
		$method->invoke($this->component);

		$this->assertNotContains('superPrivate', $this->component->getUnauthenticatedActions());
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
		$request = new ServerRequest(['params' => [
			'controller' => 'Offers',
			'action' => 'view',
			'plugin' => null,
			'_ext' => null,
			'pass' => [],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
	}

	/**
	 * Regression: a rule scoped to a prefix must not match a request without one.
	 *
	 * `admin/my_prefix/MyTest = myPublic` is prefix-scoped. A non-prefix request
	 * to `MyTest::myPublic` must not pull in the prefix-scoped rule.
	 *
	 * @return void
	 */
	public function testIsPublicDoesNotMatchPrefixRuleForNonPrefixRequest() {
		$request = new ServerRequest(['params' => [
			'controller' => 'MyTest',
			'action' => 'myPublic',
			'plugin' => null,
			'prefix' => null,
			'_ext' => null,
			'pass' => [],
		]]);
		$controller = $this->getControllerMock($request);
		$registry = new ComponentRegistry($controller);
		$this->component = new AuthenticationComponent($registry, $this->componentConfig);

		$result = $this->component->isPublic();
		$this->assertFalse($result);
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
