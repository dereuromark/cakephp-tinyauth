<?php

namespace TinyAuth\Test\TestCase\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use TestApp\Auth\AclAdapter\CustomAclAdapter;
use TestApp\Auth\TestTinyAuthorize;

/**
 * Test case for TinyAuth Authentication
 */
class TinyAuthorizeTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.TinyAuth.Users',
		'plugin.TinyAuth.DatabaseRoles',
		'plugin.TinyAuth.EmptyRoles',
		'plugin.TinyAuth.RolesUsers',
		'plugin.TinyAuth.DatabaseRolesUsers',
		'plugin.TinyAuth.DatabaseUserRoles',
	];

	/**
	 * @var \Cake\Controller\ComponentRegistry
	 */
	public $collection;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	public $request;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->request = new ServerRequest();
		$this->collection = new ComponentRegistry(new Controller($this->request));

		Configure::write('Roles', [
			'user' => ROLE_USER,
			'moderator' => ROLE_MODERATOR,
			'admin' => ROLE_ADMIN,
		]);

		Configure::write('TinyAuth', [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		]);
	}

	/**
	 * Test applying config in the constructor
	 *
	 * @return void
	 */
	public function testConstructor() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'AuthRoles',
			'roleColumn' => 'auth_role_id',
		]);
		$this->assertEquals('AuthRoles', $object->getConfig('rolesTable'));
		$this->assertEquals('auth_role_id', $object->getConfig('roleColumn'));
	}

	/**
	 * Tests loading a valid, custom acl adapter works.
	 *
	 * @return void
	 */
	public function testLoadingAclAdapter() {
		$object = new TestTinyAuthorize($this->collection);
		$this->assertInstanceOf(CustomAclAdapter::class, $object->getAclAdapter(CustomAclAdapter::class));
	}

	/**
	 * Tests loading an invalid acl adapter fails.
	 *
	 * @return void
	 */
	public function testLoadingInvalidAclAdapter() {
		$object = new TestTinyAuthorize($this->collection, [
			'aclAdapter' => Configure::class,
		]);

		$this->expectException(InvalidArgumentException::class);

		$object->getAcl();
	}

	/**
	 * Tests setting a non-existent class as the acl adapter fails.
	 *
	 * @return void
	 */
	public function testLoadingNonExistentAclAdapter() {
		$object = new TestTinyAuthorize($this->collection, [
			'aclAdapter' => 'Non\\Existent\\Acl\\Adapter',
		]);

		$this->expectException(CakeException::class);

		$object->getAcl();
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);
		$res = $object->getAcl();

		$expected = [
			'Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'index' => ['user' => ROLE_USER],
					'edit' => ['user' => ROLE_USER],
					'delete' => ['admin' => ROLE_ADMIN],
					'very_long_underscored_action' => ['user' => ROLE_USER],
					'veryLongActionNameAction' => ['user' => ROLE_USER],
				],
				'deny' => [],
			],
			'Admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'Admin',
				'plugin' => null,
				'allow' => [
					'index' => ['user' => ROLE_USER],
					'edit' => ['user' => ROLE_USER],
					'delete' => ['admin' => ROLE_ADMIN],
					'very_long_underscored_action' => ['user' => ROLE_USER],
					'veryLongActionNameAction' => ['user' => ROLE_USER],
				],
				'deny' => [],
			],
			'Tags.Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => 'Tags',
				'allow' => [
					'index' => ['user' => ROLE_USER],
					'edit' => ['user' => ROLE_USER],
					'view' => ['user' => ROLE_USER],
					'delete' => ['admin' => ROLE_ADMIN],
					'very_long_underscored_action' => ['user' => ROLE_USER],
					'veryLongActionNameAction' => ['user' => ROLE_USER],
				],
				'deny' => [],
			],
			'Tags.Admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'Admin',
				'plugin' => 'Tags',
				'allow' => [
					'index' => ['user' => ROLE_USER],
					'edit' => ['user' => ROLE_USER],
					'view' => ['user' => ROLE_USER],
					'delete' => ['admin' => ROLE_ADMIN],
					'very_long_underscored_action' => ['user' => ROLE_USER],
					'veryLongActionNameAction' => ['user' => ROLE_USER],
				],
				'deny' => [],
			],
			'Special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'Special',
				'plugin' => null,
				'allow' => [
					'*' => ['admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Comments.Special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'Special',
				'plugin' => 'Comments',
				'allow' => [
					'*' => ['admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'*' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR, 'admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'Admin',
				'plugin' => null,
				'allow' => [
					'*' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR, 'admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Posts.Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => 'Posts',
				'allow' => [
					'*' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR, 'admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Posts.Admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'Admin',
				'plugin' => 'Posts',
				'allow' => [
					'*' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR, 'admin' => ROLE_ADMIN],
				],
				'deny' => [],
			],
			'Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'*' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR],
				],
				'deny' => [
					'foo' => ['user' => ROLE_USER],
				],
			],
			'Admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'Admin',
				'plugin' => null,
				'allow' => [
					'*' => ['moderator' => ROLE_MODERATOR],
				],
				'deny' => [],
			],
			'Blogs.Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => 'Blogs',
				'allow' => [
					'*' => ['moderator' => ROLE_MODERATOR],
				],
				'deny' => [],
			],
			'Blogs.Admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'Admin',
				'plugin' => 'Blogs',
				'allow' => [
					'*' => ['moderator' => ROLE_MODERATOR],
				],
				'deny' => [],
			],
			'Admin/MyPrefix/MyTest' => [
				'controller' => 'MyTest',
				'prefix' => 'Admin/MyPrefix',
				'plugin' => null,
				'allow' => [
					'myAll' => ['user' => ROLE_USER, 'moderator' => ROLE_MODERATOR, 'admin' => ROLE_ADMIN],
					'myModerator' => ['moderator' => ROLE_MODERATOR],
					'myDenied' => ['admin' => ROLE_ADMIN],
				],
				'deny' => [
					'myDenied' => ['moderator' => ROLE_MODERATOR],
				],
			],
		];
		// We don't need the original map
		foreach ($res as &$r) {
			unset($r['map']);
		}
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodInexistentRole() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		$user = ['role_id' => 99]; // invalid non-existing role
		$result = $object->authorize($user, $this->request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodDisallowed() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);
		$this->assertEquals('Roles', $object->getConfig('rolesTable'));
		$this->assertEquals('role_id', $object->getConfig('roleColumn'));
		$this->assertEquals('id', $object->getConfig('idColumn'));

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'add');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Tags');

		$user = ['role_id' => ROLE_USER]; // valid role without authorization
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin')
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$this->request = $this->request->withParam('action', 'edit');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request = $this->request->withParam('action', 'delete');
		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$this->request = $this->request->withParam('action', 'edit');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$this->request = $this->request->withParam('action', 'delete');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$this->request = $this->request->withParam('action', 'edit');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$this->request = $this->request->withParam('action', 'delete');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$this->request = $this->request->withParam('action', 'edit');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$this->request = $this->request->withParam('action', 'delete');
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests using incorrect casing, enforces strict acl.ini definitions.
	 *
	 * @return void
	 */
	public function testCaseSensitivity() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'index');

		// Test incorrect controller casing
		$this->request = $this->request->withParam('controller', 'tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect controller casing with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct controller casing with incorrect prefix casing
		$this->request = $this->request->withParam('controller', 'Users')
			->withParam('prefix', 'admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect plugin controller casing
		$this->request = $this->request->withParam('controller', 'tags')
			->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with incorrect plugin casing
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', null)
			->withParam('plugin', 'tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with correct plugin but incorrect prefix casing
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', 'admin')
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'veryLongActionNameAction');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNamesUnderscored() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'very_long_underscored_action');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin')
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Tests multi-role authorization.
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		// Test against roles array in Configure
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'Roles',
		]);

		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('action', 'delete');

		// User 1 has roles 1 (user) and 2 (moderator): admin required for the delete.
		$user = ['id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// User 2 has roles 1 (user) and 3 (admin): admin required for the delete.
		$user = ['id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test against roles array in Database
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'roleColumn' => 'database_role_id',
		]);

		// User 1 has roles 11 (user) and 12 (moderator): admin required for the delete.
		$user = ['id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// User 2 has roles 11 (user) and 13 (admin): admin required for the delete.
		$user = ['id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests access to a controller that uses the * wildcard for both the
	 * action and the allowed groups (* = *).
	 *
	 * Note: users without a valid/defined role will not be granted access.
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedWildcard() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Posts');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Posts');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for plugin controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin')
			->withParam('plugin', 'Posts');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests access to a controller that uses the * wildcard for the action
	 * but combines it with a specific group (here: * = moderators).
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedWildcardSpecificGroup() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Blogs');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controlller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Blogs');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controlller with /admin prefix
		$this->request = $this->request->withParam('prefix', 'Admin')
			->withParam('plugin', 'Blogs');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Tests with deny even though wildcard allows all for this role.
	 *
	 * @return void
	 */
	public function testDeny() {
		$object = new TestTinyAuthorize($this->collection, [
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'foo');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Blogs');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testAllowNestedPrefix() {
		$object = new TestTinyAuthorize($this->collection, [
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'myModerator');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'MyTest')
			->withParam('prefix', 'Admin/MyPrefix');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test different prefix
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Tests with configuration setting 'allowLoggedIn' set to true, giving user
	 * access to all controller/actions except when prefixed with /admin
	 *
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$object = new TestTinyAuthorize($this->collection, [
			'allowLoggedIn' => true,
			'protectedPrefix' => 'Admin',
		]);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller
		$this->request = $this->request->withParam('controller', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix. Note: users should NOT
		// be allowed access here since the prefix matches the  'protectedPrefix'
		// configuration setting.
		$this->request = $this->request->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request = $this->request->withParam('prefix', null)
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix. Again: access should
		// NOT be allowed because of matching 'protectedPrefix'
		$this->request = $this->request->withParam('prefix', 'Admin')
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test access to a standard controller using a prefix not matching the
		// 'protectedPrefix' => users should be allowed access.
		$this->request = $this->request->withParam('controller', 'Comments')
			->withParam('prefix', 'Special')
			->withParam('plugin', null);

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test access to a plugin controller using a prefix not matching the
		// 'protectedPrefix' => users should be allowed access.
		$this->request = $this->request->withParam('plugin', 'Comments');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_MODERATOR];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Test with enabled configuration settings - access to all actions that are
	 * prefixed using the same role configuration setting.
	 *
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$config = [
			'authorizeByPrefix' => ['Admin'],
			'autoClearCache' => true,
		];
		$object = new TestTinyAuthorize($this->collection, $config);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', 'Admin')
			->withParam('plugin', 'Tags');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testAuthorizeRolesAsPrefix() {
		$config = [
			'authorizeByPrefix' => true,
			'autoClearCache' => true,
		];
		$object = new TestTinyAuthorize($this->collection, $config);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller with /admin prefix
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', 'Admin');

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests prefix => role(s) mapping
	 *
	 * @return void
	 */
	public function testAdminMethodsAllowedPrefixMap() {
		$config = [
			'authorizeByPrefix' => ['Management' => 'admin', 'Cool' => ['foo', 'bar', 'user']],
			'autoClearCache' => true,
		];
		$object = new TestTinyAuthorize($this->collection, $config);

		// All tests performed against this action
		$this->request = $this->request->withParam('action', 'any_action');

		// Test standard controller with /management prefix
		$this->request = $this->request->withParam('controller', 'Tags')
			->withParam('prefix', 'Management')
			->withParam('plugin', null);

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /cool prefix
		$this->request = $this->request->withParam('prefix', 'Cool');

		$user = ['role_id' => ROLE_ADMIN];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests superAdmin role, allowed to all actions
	 *
	 * @return void
	 */
	public function testSuperAdminRole() {
		$object = new TestTinyAuthorize($this->collection, [
			'superAdminRole' => 9,
		]);
		$acl = $object->getAcl();
		$user = [
			'role_id' => 9,
		];

		foreach ($acl as $resource) {
			foreach ($resource['allow'] as $action => $allowed) {
				$this->request = $this->request->withAttribute('action', $action)
					->withAttribute('controller', $resource['controller'])
					->withAttribute('prefix', $resource['prefix']);
				$res = $object->authorize($user, $this->request);
				$this->assertTrue($res);
			}
		}
	}

	/**
	 * Tests acl.ini parsing method.
	 *
	 * @return void
	 */
	public function testIniParsing() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_parseFiles');
		$method->setAccessible(true);
		$res = $method->invokeArgs($object, [
			[
				Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
				Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS . 'subfolder' . DS,
			],
			'auth_acl.ini',
		]);
		$this->assertTrue(is_array($res));

		$this->assertSame(['*' => 'moderator'], $res['Blogs.Blogs']);
		$this->assertSame(['index' => 'admin'], $res['Foo']);
	}

	/**
	 * Tests exception thrown when no acl.ini exists.
	 *
	 * @return void
	 */
	public function testIniParsingMissingFileException() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true,
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_parseFiles');
		$method->setAccessible(true);

		$this->expectException(CakeException::class);

		$method->invokeArgs($object, [
			Plugin::path('TinyAuth') . 'non' . DS . 'existent' . DS,
			'auth_acl.ini']);
	}

	/**
	 * Tests constructing an ACL ini section key using CakeRequest parameters
	 *
	 * @return void
	 */
	public function testIniConstruct() {
		// Make protected function accessible
		$object = new TestTinyAuthorize($this->collection);
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_constructIniKey');
		$method->setAccessible(true);

		// Test standard controller
		$params = [
			'controller' => 'Tags',
			'prefix' => null,
			'plugin' => null,
		];

		$expected = 'Tags';
		$res = $method->invokeArgs($object, [$params]);
		$this->assertEquals($expected, $res);

		// Test standard controller with /admin prefix
		$params = [
			'controller' => 'Tags',
			'prefix' => 'Admin',
			'plugin' => null,
		];

		$expected = 'Admin/Tags';
		$res = $method->invokeArgs($object, [$params]);
		$this->assertEquals($expected, $res);

		// Test plugin controller
		$params = [
			'controller' => 'Tags',
			'prefix' => null,
			'plugin' => 'Tags',
		];

		$expected = 'Tags.Tags';
		$res = $method->invokeArgs($object, [$params]);
		$this->assertEquals($expected, $res);

		// Test plugin controller with /admin prefix
		$params = [
			'controller' => 'Tags',
			'prefix' => 'Admin',
			'plugin' => 'Tags',
		];

		$expected = 'Tags.Admin/Tags';
		$res = $method->invokeArgs($object, [$params]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests deconstructing an ACL ini section key
	 *
	 * @return void
	 */
	public function testIniDeconstruct() {
		// Make protected function accessible
		$object = new TestTinyAuthorize($this->collection);
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_deconstructIniKey');
		$method->setAccessible(true);

		// Test standard controller
		$key = 'Tags';
		$expected = [
			'controller' => 'Tags',
			'plugin' => null,
			'prefix' => null,
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'tags'; // test incorrect casing
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test standard controller with /admin prefix
		$key = 'Admin/Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => 'Admin',
			'plugin' => null,
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test plugin controller without prefix
		$key = 'Tags.Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => null,
			'plugin' => 'Tags',
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'tags/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'tags/Tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Tags/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test plugin controller with /admin prefix
		$key = 'Tags.Admin/Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => 'Admin',
			'plugin' => 'Tags',
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'tags.admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'tags.Admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'tags.admin/Tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Tags.admin/Tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'Tags.Admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);
	}

	/**
	 * Tests fetching available Roles from Configure and database
	 *
	 * @return void
	 */
	public function testAvailableRoles() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'Roles',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);

		// Test against roles array in Configure
		$expected = [
			'user' => ROLE_USER,
			'moderator' => ROLE_MODERATOR,
			'admin' => ROLE_ADMIN,
		];
		$res = $method->invoke($object);
		$this->assertEquals($expected, $res);

		// Test against roles from database
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'DatabaseRoles',
		]);
		$expected = [
			'user' => 11,
			'moderator' => 12,
			'admin' => 13,
		];
		$res = $method->invoke($object);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests exception thrown when no roles are in Configure AND the roles
	 * database table does not exist.
	 *
	 * @return void
	 */
	public function testAvailableRolesMissingTableException() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'NonExistentTable',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);

		$this->expectException(Exception::class);

		$method->invoke($object);
	}

	/**
	 * Tests exception thrown when the roles database table exists but contains
	 * no roles/records.
	 *
	 * @return void
	 */
	public function testAvailableRolesEmptyTableException() {
		$object = new TestTinyAuthorize($this->collection, [

			'rolesTable' => 'EmptyRoles',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);

		$this->expectException(Exception::class);

		$method->invoke($object);
	}

	/**
	 * Tests fetching user roles
	 *
	 * @return void
	 */
	public function testUserRoles() {
		$object = new TestTinyAuthorize($this->collection, [

			'multiRole' => false,
			'roleColumn' => 'role_id',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		// Single-role: get role id from roleColumn in user table
		$user = ['role_id' => ROLE_USER];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertSame(['user' => ROLE_USER], $res);

		// Multi-role: lookup roles directly in pivot table
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'roleColumn' => 'database_role_id',
		]);
		$user = ['id' => 2];
		$expected = [
			'user' => 11,
			'admin' => 13,
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests fetching user roles
	 *
	 * @return void
	 */
	public function testUserRolesCustomPivotTable() {
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		$user = ['id' => 2];
		$expected = [
			'user' => 11,
			'admin' => 13,
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests idColumn
	 *
	 * @return void
	 */
	public function testIdColumnPivotTable() {
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'idColumn' => 'profile_id',
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		$user = [
			'id' => 1,
			'profile_id' => 2,
		];
		$expected = [
			'user' => 11,
			'admin' => 13,
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);

		$user = [
			'id' => 1,
			'profile_id' => 1,
		];
		$expected = [
			'user' => 11,
			'moderator' => 12,
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);

		//without id
		$user = [
			'profile_id' => 1,
		];
		$expected = [
			'user' => 11,
			'moderator' => 12,
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests super admin
	 *
	 * @return void
	 */
	public function testSuperAdmin() {
		// All tests performed against this action
		$this->request = $this->request->withAttribute('action', 'any_action')
			->withAttribute('controller', 'AnyControllers')
			->withAttribute('prefix', null)
			->withAttribute('plugin', null);

		// Single role
		$object = new TestTinyAuthorize($this->collection, [
			'superAdmin' => 1,
		]);

		$user = ['id' => 1, 'role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 2, 'role_id' => ROLE_USER];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'group_id',
			'superAdmin' => 1,
		]);
		$user = ['id' => 100, 'role_id' => ROLE_USER, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => ROLE_USER, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'any_id_column',
			'superAdminColumn' => 'group_id',
			'superAdmin' => 1,
		]);
		$user = ['id' => 100, 'role_id' => ROLE_USER, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => ROLE_USER, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn without idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'superAdminColumn' => 'group_id',
			'superAdmin' => 1,
		]);
		$user = ['id' => 100, 'role_id' => ROLE_USER, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => ROLE_USER, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn (string)
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'any_id_column',
			'superAdminColumn' => 'group',
			'superAdmin' => 'Admin',
		]);
		$user = ['id' => 100, 'role_id' => ROLE_USER, 'group' => 'admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['id' => 100, 'role_id' => ROLE_USER, 'group' => 'Admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => ROLE_USER, 'group' => 'authors'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//multi role
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'superAdmin' => 1,
		]);
		$user = ['id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//multi role and idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'idColumn' => 'profile_id',
			'superAdmin' => 1,
		]);
		$user = ['id' => 100, 'profile_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'profile_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//multi role and superAdminColumn
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'idColumn' => 'another_id',
			'superAdminColumn' => 'group_id',
			'superAdmin' => 100,
		]);

		$user = ['another_id' => 1, 'group_id' => 100];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['another_id' => 2, 'group_id' => 102];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//multi role and superAdminColumn without idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'superAdminColumn' => 'group_id',
			'superAdmin' => 100,
		]);

		$user = ['id' => 1, 'group_id' => 100];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 2, 'group_id' => 102];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn (string)
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'superAdminColumn' => 'group',
			'superAdmin' => 'Admin',
		]);

		$user = ['id' => 1, 'group' => 'admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['id' => 1, 'group' => 'Admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 2, 'group' => 'Authors'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Tests single-role exception thrown when the roleColumn field is missing
	 * from the user table.
	 *
	 * @return void
	 */
	public function testUserRolesMissingRoleColumn() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'NonExistentTable',
			'multiRole' => false,
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		$this->expectException(Exception::class);

		$user = ['id' => 1];
		$method->invokeArgs($object, [$user]);
	}

	/**
	 * Tests multi-role when user has no roles in the pivot table.
	 *
	 * @return void
	 */
	public function testUserRolesUserWithoutPivotRoles() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'Roles',
			'multiRole' => true,
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		$user = ['id' => 5];
		$result = $method->invokeArgs($object, [$user]);
		$this->assertSame([], $result);
	}

}
