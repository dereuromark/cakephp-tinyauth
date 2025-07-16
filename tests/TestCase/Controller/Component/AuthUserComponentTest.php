<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use ArrayAccess;
use ArrayIterator;
use Authentication\Identity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Authorization\Policy\ResultInterface;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use IteratorAggregate;
use TestApp\Controller\Component\TestAuthUserComponent;
use TinyAuth\Controller\Component\AuthComponent;
use TinyAuth\Utility\Cache;
use Traversable;

class AuthUserComponentTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'core.Sessions',
	];

	/**
	 * @var \TinyAuth\Controller\Component\AuthUserComponent
	 */
	protected $AuthUser;

	/**
	 * @var \Cake\Controller\Controller
	 */
	protected $controller;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$config = [
			'allowFilePath' => ROOT . DS . 'tests' . DS . 'test_files' . DS,
		];

		$this->controller = new Controller(new ServerRequest());
		$componentRegistry = new ComponentRegistry($this->controller);
		$this->AuthUser = new TestAuthUserComponent($componentRegistry);
		$this->controller->loadComponent('TinyAuth.Auth', [
			'allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
		]);
		$this->controller->Auth = $this->getMockBuilder(AuthComponent::class)->onlyMethods(['user'])->setConstructorArgs([$componentRegistry, $config])->getMock();

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
		]);
		$this->AuthUser->setConfig('autoClearCache', true);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedValid() {
		$user = [
			'id' => 1,
			'role_id' => 1,
		];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

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
			'role_id' => 1,
		];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

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
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

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
		$this->AuthUser->setConfig('autoClearCache', false);
		$data = [
			'Users' => [
				'controller' => 'Users',
				'allow' => ['view'],
			],
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
		$this->AuthUser->setConfig('autoClearCache', false);

		$data = [
			'Users' => [
				'controller' => 'Users',
				'allow' => ['index'],
			],
		];
		Cache::write(Cache::KEY_ALLOW, $data);

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

		$this->assertSame([], $controller->viewBuilder()->getVar('_authUser'));
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
		$user = ['id' => '1'];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertSame('1', $this->AuthUser->id());
	}

	/**
	 * @return void
	 */
	public function testIsMe() {
		$user = ['id' => '1'];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

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
		$user = ['id' => '1', 'username' => 'foo'];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['id' => '1', 'username' => 'foo'], $this->AuthUser->user());
		$this->assertSame('foo', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testUserFromIdentity() {
		$object = new Entity(['id' => 1, 'username' => 'user']);
		$identity = new Identity($object);
		$this->controller->setRequest($this->controller->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['id' => 1, 'username' => 'user'], $this->AuthUser->user());
		$this->assertSame(1, $this->AuthUser->id());
		$this->assertSame('user', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testUserFromIdentityTraversable() {
		$object = new class implements ArrayAccess, IteratorAggregate {
			/**
			 * @return \Traversable
			 */
			public function getIterator(): Traversable {
				return new ArrayIterator(['id' => 1, 'username' => 'user']);
			}

			/**
			 * @param int $offset
			 * @return bool
			 */
			public function offsetExists($offset): bool {
				return isset($this->data[$offset]);
			}

			/**
			 * @param int $offset
			 * @return mixed
			 */
			public function offsetGet($offset) {
				return $this->data[$offset];
			}

			/**
			 * @param int $offset
			 * @param mixed $value
			 * @return void
			 */
			public function offsetSet($offset, $value): void {
				$this->data[$offset] = $value;
			}

			/**
			 * @param int $offset
			 * @return void
			 */
			public function offsetUnset($offset): void {
				unset($this->data[$offset]);
			}
		};
		$identity = new Identity($object);
		$this->controller->setRequest($this->controller->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['id' => 1, 'username' => 'user'], $this->AuthUser->user());
		$this->assertSame(1, $this->AuthUser->id());
		$this->assertSame('user', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testUserFromIdentityArrayAccess() {
		$object = new class implements ArrayAccess {
			private array $data = ['id' => 1, 'username' => 'user'];

			/**
			 * @param int $offset
			 * @return bool
			 */
			public function offsetExists($offset): bool {
				return isset($this->data[$offset]);
			}

			/**
			 * @param int $offset
			 * @return mixed
			 */
			public function offsetGet($offset) {
				return $this->data[$offset];
			}

			/**
			 * @param int $offset
			 * @param mixed $value
			 * @return void
			 */
			public function offsetSet($offset, $value): void {
				$this->data[$offset] = $value;
			}

			/**
			 * @param int $offset
			 * @return void
			 */
			public function offsetUnset($offset): void {
				unset($this->data[$offset]);
			}
		};
		$identity = new Identity($object);
		$this->controller->setRequest($this->controller->getRequest()->withAttribute('identity', $identity));

		$this->expectException(InvalidArgumentException::class);

		$this->AuthUser->user();
	}

	/**
	 * @return void
	 */
	public function testUserFromDecoratedIdentity() {
		$object = new Entity(['id' => 1, 'username' => 'user']);
		$auth = new class implements AuthorizationServiceInterface {

			/**
			 * @param \Authorization\IdentityInterface|null $user
			 * @param string $action
			 * @param mixed $resource
			 * @param ...$optionalArgs
			 * @return bool
			 */
			public function can(?IdentityInterface $user, string $action, mixed $resource, ...$optionalArgs): bool {
				return false;
			}

			/**
			 * @param \Authorization\IdentityInterface|null $user
			 * @param string $action
			 * @param mixed $resource
			 * @param ...$optionalArgs
			 * @return \Authorization\Policy\ResultInterface
			 */
			public function canResult(?IdentityInterface $user, string $action, mixed $resource, ...$optionalArgs): ResultInterface {
				$x = new class implements ResultInterface {
					/**
					 * @return bool
					 */
					public function getStatus(): bool {
						return false;
					}

					/**
					 * @return string|null
					 */
					public function getReason(): ?string {
						return null;
					}
				};

				return $x;
			}

			/**
			 * @param \Authorization\IdentityInterface|null $user
			 * @param string $action
			 * @param mixed $resource
			 * @param ...$optionalArgs
			 * @return mixed
			 */
			public function applyScope(?IdentityInterface $user, string $action, mixed $resource, ...$optionalArgs): mixed {
				return null;
			}

			/**
			 * @return bool
			 */
			public function authorizationChecked(): bool {
				return false;
			}

			/**
			 * @return void
			 */
			public function skipAuthorization() {
			}
		};
		$identity = new IdentityDecorator($auth, $object);
		$this->controller->setRequest($this->controller->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['id' => 1, 'username' => 'user'], $this->AuthUser->user());
		$this->assertSame(1, $this->AuthUser->id());
		$this->assertSame('user', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testRoles() {
		$this->AuthUser->setConfig('multiRole', true);

		$user = ['id' => '1', 'Roles' => ['1', '2']];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['user' => '1', 'moderator' => '2'], $this->AuthUser->roles());
	}

	/**
	 * @return void
	 */
	public function testRolesDeep() {
		$this->AuthUser->setConfig('multiRole', true);

		$user = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertSame(['user' => '1', 'moderator' => '2'], $this->AuthUser->roles());
	}

	/**
	 * @return void
	 */
	public function testHasRole() {
		$this->AuthUser->setConfig('multiRole', true);

		$user = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

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

		$user = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertTrue($this->AuthUser->hasRoles([2]));
		$this->assertTrue($this->AuthUser->hasRoles('2'));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 2], false));

		$this->assertTrue($this->AuthUser->hasRoles([1, 6], [1, 3, 5]));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4], [2, 4]));

		$this->assertFalse($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3, 5]));
	}

	/**
	 * @return void
	 */
	public function testHasRoleHash() {
		$this->AuthUser->setConfig('roleColumn', 'Role.id');

		$user = ['id' => '1', 'Role' => ['id' => '1']];
		$identity = new Identity($user);
		$this->AuthUser->getController()->setRequest($this->AuthUser->getController()->getRequest()->withAttribute('identity', $identity));

		$this->assertTrue($this->AuthUser->hasRole(1));
		$this->assertFalse($this->AuthUser->hasRole(3));
	}

}
