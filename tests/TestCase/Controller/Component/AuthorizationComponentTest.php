<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Authentication\Identity as AuthenticationIdentity;
use Authorization\AuthorizationService;
use Authorization\Identity;
use Authorization\Policy\Result;
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
	protected array $componentConfig = [];

	/**
	 * @return void
	 */
	public function setUp(): void {
		Configure::write('Roles', [
			'user' => ROLE_USER,
			'moderator' => ROLE_MODERATOR,
			'admin' => ROLE_ADMIN,
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
		$request = new ServerRequest([
			'params' => [
				'controller' => 'Users',
				'action' => 'view',
				'plugin' => null,
				'_ext' => null,
				'pass' => [1],
			],
		]);
		/** @var \Authorization\AuthorizationService|\PHPUnit\Framework\MockObject\MockObject $authorization */
		$authorization = $this->getMockBuilder(AuthorizationService::class)->disableOriginalConstructor()->getMock();
		$authorization->expects($this->once())
			->method('canResult')
			->willReturn(new Result(true));
		$identity = new Identity($authorization, new AuthenticationIdentity([]));

		$request = $request->withAttribute('authorization', $authorization)
			->withAttribute('identity', $identity);
		$controller = new Controller($request);

		$registry = new ComponentRegistry($controller);
		$this->component = new AuthorizationComponent($registry, $this->componentConfig);

		$this->component->authorizeAction();

		$request = $this->component->getController()->getRequest();
		/** @var \Authorization\AuthorizationService $service */
		$service = $request->getAttribute('authorization');

		$this->assertInstanceOf(AuthorizationService::class, $service);
	}

}
