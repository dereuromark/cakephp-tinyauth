<?php

namespace TinyAuth\Test\TestCase\Policy;

use Authorization\AuthorizationService;
use Authorization\IdentityDecorator;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TinyAuth\Policy\RequestPolicy;

class RequestPolicyTest extends TestCase {

	/**
	 * @var \TinyAuth\Controller\Component\AuthorizationComponent
	 */
	protected $policy;

	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * @return void
	 */
	public function setUp(): void {
		Configure::write('Roles', [
			'user' => ROLE_USER,
			'moderator' => ROLE_MODERATOR,
			'admin' => ROLE_ADMIN,
		]);

		$this->config = [
			'aclFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];

		$this->policy = new RequestPolicy($this->config);
	}

	/**
	 * @return void
	 */
	public function testPolicyCanAccessSuccess() {
		$request = new ServerRequest([
			'params' => [
				'controller' => 'Tags',
				'action' => 'delete',
				'plugin' => null,
			],
		]);

		$identityArray = [
			'id' => 1,
			'role_id' => ROLE_ADMIN,
		];
		$service = $this->getService();
		$identity = new IdentityDecorator($service, $identityArray);
		$result = $this->policy->canAccess($identity, $request);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testPolicyCanAccessFail() {
		$request = new ServerRequest([
			'params' => [
				'controller' => 'Tags',
				'action' => 'edit',
				'plugin' => null,
			],
		]);

		$identityArray = [
			'id' => 1,
			'role_id' => ROLE_ADMIN,
		];
		$service = $this->getService();
		$identity = new IdentityDecorator($service, $identityArray);
		$result = $this->policy->canAccess($identity, $request);
		$this->assertFalse($result);
	}

	/**
	 * @return \Authorization\AuthorizationService|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getService() {
		return $this->getMockBuilder(AuthorizationService::class)->disableOriginalConstructor()->getMock();
	}

}
