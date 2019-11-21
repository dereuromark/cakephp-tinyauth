<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestApp\Controller\Component\TestAuthUserComponent;
use TinyAuth\Utility\Cache;

class AuthUserComponentTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'core.Sessions'
	];

	/**
	 * @var \TinyAuth\Controller\Component\AuthUserComponent
	 */
	protected $AuthUser;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$controller = new Controller(new ServerRequest());
		$componentRegistry = new ComponentRegistry($controller);
		$this->AuthUser = new TestAuthUserComponent($componentRegistry);
		$this->AuthUser->Auth = $this->getMockBuilder(AuthComponent::class)->setMethods(['user'])->setConstructorArgs([$componentRegistry])->getMock();

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3
		]);
		$this->AuthUser->setConfig('autoClearCache', true);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedValid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue($user));

		$request = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUser->hasAccess($request);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedInvalid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue($user));

		$request = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUser->hasAccess($request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedNotLoggedIn() {
		$user = [
		];
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue($user));

		$request = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUser->hasAccess($request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testHasAccessPublicCache() {
		$this->AuthUser->setConfig('includeAuthentication', true);
		$data = [
			'Users' => [
				'controller' => 'Users',
				'allow' => ['view'],
			]
		];
		Cache::write(Cache::KEY_ALLOW, $data);

		$request = [
			'controller' => 'Users',
			'action' => 'view',
		];
		$result = $this->AuthUser->hasAccess($request);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testHasAccessPublicInvalid() {
		$this->AuthUser->setConfig('includeAuthentication', true);
		$cache = '_cake_core_';
		$cacheKey = 'allow';
		Configure::write('TinyAuth.cache', $cache);
		Configure::write('TinyAuth.cachePrefix', 'tiny_auth_');
		Configure::write('TinyAuth.cacheKey', $cacheKey);

		$data = [
			'Users' => [
				'controller' => 'Users',
				'allow' => ['index'],
			]
		];
		Cache::write($cacheKey, $data, $cache);

		$request = [
			'controller' => 'Users',
			'action' => 'view',
		];
		$result = $this->AuthUser->hasAccess($request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testBeforeRenderSetAuthUser() {
		$controller = new Controller(new ServerRequest());
		$event = new Event('Controller.beforeRender', $controller);
		$this->AuthUser->beforeRender($event);

		$this->assertSame([], $controller->viewVars['_authUser']);
	}

	/**
	 * @return void
	 */
	public function testEmptyAuthSession() {
		$this->assertNull($this->AuthUser->id());

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));
		$this->assertFalse($this->AuthUser->isMe(1));
	}

	/**
	 * @return void
	 */
	public function testId() {
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1']));

		$this->assertSame('1', $this->AuthUser->id());
	}

	/**
	 * @return void
	 */
	public function testIsMe() {
		$this->AuthUser->Auth->expects($this->any())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1']));

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));

		$this->assertTrue($this->AuthUser->isMe('1'));
		$this->assertTrue($this->AuthUser->isMe(1));
	}

	/**
	 * @return void
	 */
	public function testUser() {
		$this->AuthUser->Auth->expects($this->any())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'username' => 'foo']));

		$this->assertSame(['id' => '1', 'username' => 'foo'], $this->AuthUser->user());
		$this->assertSame('foo', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testRoles() {
		$this->AuthUser->setConfig('multiRole', true);

		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->will($this->returnValue(['id' => '1', 'Roles' => ['1', '2']]));

		$this->assertSame(['user' => '1', 'moderator' => '2'], $this->AuthUser->roles());
	}

	/**
	 * @return void
	 */
	public function testRolesDeep() {
		$this->AuthUser->setConfig('multiRole', true);

		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

		$this->assertSame(['user' => '1', 'moderator' => '2'], $this->AuthUser->roles());
	}

	/**
	 * @return void
	 */
	public function testHasRole() {
		$this->AuthUser->setConfig('multiRole', true);

		$this->AuthUser->Auth->expects($this->exactly(3))
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

		$this->assertTrue($this->AuthUser->hasRole(2));
		$this->assertTrue($this->AuthUser->hasRole('2'));
		$this->assertFalse($this->AuthUser->hasRole(3));

		$this->assertTrue($this->AuthUser->hasRole(3, [1, 3]));
		$this->assertFalse($this->AuthUser->hasRole(3, [2, 4]));
	}

	/**
	 * @return void
	 */
	public function testHasRoles() {
		$this->AuthUser->setConfig('multiRole', true);

		$this->AuthUser->Auth->expects($this->exactly(6))
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

		$this->assertTrue($this->AuthUser->hasRoles([2]));
		$this->assertTrue($this->AuthUser->hasRoles('2'));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 2], false));

		$this->assertTrue($this->AuthUser->hasRoles([1, 6], [1, 3, 5]));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4], [2, 4]));

		$this->assertFalse($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3, 5]));
	}

}
