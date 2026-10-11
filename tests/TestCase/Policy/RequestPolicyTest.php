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
	protected array $config = [];

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
	 * @return \Authorization\AuthorizationService
	 */
	protected function getService(): AuthorizationService {
		return $this->createStub(AuthorizationService::class);
	}

	/**
	 * @return void
	 */
	public function testIncludedAuthenticationHonorsWildcardDenyAndPublicDefaults(): void {
		$config = $this->config + [
			'includeAuthentication' => true,
			'allowFilePath' => $this->config['aclFilePath'],
			'allowFile' => 'deny_allow.ini',
		];
		$request = new ServerRequest(['params' => ['controller' => 'MixedDeny', 'action' => 'index']]);
		$this->assertFalse((new RequestPolicy($config))->canAccess(null, $request));

		$request = new ServerRequest(['params' => ['controller' => 'Unlisted', 'action' => 'index']]);
		$this->assertTrue((new RequestPolicy($config + ['allowNonPrefixed' => true]))->canAccess(null, $request));
		$request = $request->withParam('prefix', 'Admin/Sub');
		$this->assertTrue((new RequestPolicy($config + ['allowPrefixes' => ['Admin/Sub']]))->canAccess(null, $request));
	}

}
