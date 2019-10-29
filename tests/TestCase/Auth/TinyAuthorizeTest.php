<?php
namespace TinyAuth\Test\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
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
	public $fixtures = [
		'plugin.TinyAuth.Users',
		'plugin.TinyAuth.DatabaseRoles',
		'plugin.TinyAuth.EmptyRoles',
		'plugin.TinyAuth.RolesUsers',
		'plugin.TinyAuth.DatabaseRolesUsers',
		'plugin.TinyAuth.DatabaseUserRoles'
	];

	/**
	 * @var \Cake\Controller\ComponentRegistry
	 */
	public $collection;

	/**
	 * @var \Cake\Http\ServerRequest|\Cake\Network\Request
	 */
	public $request;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->collection = new ComponentRegistry();

		$this->request = new Request();

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3
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
			'roleColumn' => 'auth_role_id'
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
		$object = new TestTinyAuthorize($this->collection, [
			'aclAdapter' => CustomAclAdapter::class
		]);
		$this->assertInstanceOf(CustomAclAdapter::class, $object->getAclAdapter());
	}

	/**
	 * Tests loading an invalid acl adapter fails.
	 *
	 * @expectedException \InvalidArgumentException
	 * @return void
	 */
	public function testLoadingInvalidAclAdapter() {
		new TestTinyAuthorize($this->collection, [
			'aclAdapter' => Configure::class
		]);
	}

	/**
	 * Tests setting a non-existent class as the acl adapter fails.
	 *
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testLoadingNonExistentAclAdapter() {
		new TestTinyAuthorize($this->collection, [
			'aclAdapter' => 'Non\\Existent\\Acl\\Adapter'
		]);
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true
		]);
		$res = $object->getAcl();

		$expected = [
			'Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				],
				'deny' => [],
			],
			'admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'admin',
				'plugin' => null,
				'allow' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				],
				'deny' => [],
			],
			'Tags.Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => 'Tags',
				'allow' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'delete' => [3],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				],
				'deny' => [],
			],
			'Tags.admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'admin',
				'plugin' => 'Tags',
				'allow' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'delete' => [3],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				],
				'deny' => [],
			],
			'special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'special',
				'plugin' => null,
				'allow' => [
					'*' => [3]
				],
				'deny' => [],
			],
			'Comments.special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'special',
				'plugin' => 'Comments',
				'allow' => [
					'*' => [3]
				],
				'deny' => [],
			],
			'Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'*' => [1, 2, 3]
				],
				'deny' => [],
			],
			'admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'admin',
				'plugin' => null,
				'allow' => [
					'*' => [1, 2, 3]
				],
				'deny' => [],
			],
			'Posts.Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => 'Posts',
				'allow' => [
					'*' => [1, 2, 3]
				],
				'deny' => [],
			],
			'Posts.admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'admin',
				'plugin' => 'Posts',
				'allow' => [
					'*' => [1, 2, 3]
				],
				'deny' => [],
			],
			'Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => null,
				'allow' => [
					'*' => [1, 2]
				],
				'deny' => [
					'foo' => [1]
				],
			],
			'admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'admin',
				'plugin' => null,
				'allow' => [
					'*' => [2]
				],
				'deny' => [],
			],
			'Blogs.Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => 'Blogs',
				'allow' => [
					'*' => [2]
				],
				'deny' => [],
			],
			'Blogs.admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'admin',
				'plugin' => 'Blogs',
				'allow' => [
					'*' => [2]
				],
				'deny' => [],
			]
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
			'autoClearCache' => true
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
			'autoClearCache' => true
		]);
		$this->assertEquals('Roles', $object->getConfig('rolesTable'));
		$this->assertEquals('role_id', $object->getConfig('roleColumn'));
		$this->assertEquals('id', $object->getConfig('idColumn'));

		// All tests performed against this action
		$this->request->params['action'] = 'add';

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1]; // valid role without authorization
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true
		]);

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$this->request->params['action'] = 'edit';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['action'] = 'delete';
		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$this->request->params['action'] = 'edit';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$this->request->params['action'] = 'edit';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$this->request->params['action'] = 'edit';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
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
			'autoClearCache' => true
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'index';

		// Test incorrect controller casing
		$this->request->params['controller'] = 'tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect controller casing with /admin prefix
		$this->request->params['controller'] = 'tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct controller casing with incorrect prefix casing
		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'Admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect plugin controller casing
		$this->request->params['controller'] = 'tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with incorrect plugin casing
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with correct plugin but incorrect prefix casing
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'Admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'veryLongActionNameAction';

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNamesUnderscored() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'very_long_underscored_action';

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
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
			'rolesTable' => 'Roles'
		]);

		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'delete';

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

		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'delete';

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
			'autoClearCache' => true
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'any_action';

		// Test standard controller
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for standard controller with /admin prefix
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for plugin controller
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Posts';

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test *=* for plugin controller with /admin prefix
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Posts';

		$user = ['role_id' => 2];
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
			'autoClearCache' => true
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'any_action';

		// Test standard controller
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controlller
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Blogs';

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controlller with /admin prefix
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Blogs';

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
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
		$this->request->params['action'] = 'foo';

		// Test standard controller
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Tests with configuration setting 'allowUser' set to true, giving user
	 * access to all controller/actions except when prefixed with /admin
	 *
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$object = new TestTinyAuthorize($this->collection, [
			'allowUser' => true,
			'adminPrefix' => 'admin'
		]);

		// All tests performed against this action
		$this->request->params['action'] = 'any_action';

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix. Note: users should NOT
		// be allowed access here since the prefix matches the  'adminPrefix'
		// configuration setting.
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix. Again: access should
		// NOT be allowed because of matching 'adminPrefix'
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test access to a standard controller using a prefix not matching the
		// 'adminPrefix' => users should be allowed access.
		$this->request->params['controller'] = 'Comments';
		$this->request->params['prefix'] = 'special';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test access to a plugin controller using a prefix not matching the
		// 'adminPrefix' => users should be allowed access.
		$this->request->params['controller'] = 'Comments';
		$this->request->params['prefix'] = 'special';
		$this->request->params['plugin'] = 'Comments';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Test with enabled configuration settings - access to all actions that are
	 * prefixed using the same role configuration setting.
	 *
	 * TODO: also allow mapping of "prefix" => "role" for more flexibility
	 *
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$config = [
			'authorizeByPrefix' => true,
			'adminRole' => 3,
			'prefixes' => ['admin'],
			'autoClearCache' => true
		];
		$object = new TestTinyAuthorize($this->collection, $config);

		// All tests performed against this action
		$this->request->params['action'] = 'any_action';

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
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
			'superAdminRole' => 9
		]);
		$acl = $object->getAcl();
		$user = [
			'role_id' => 9
		];

		foreach ($acl as $resource) {
			foreach ($resource['allow'] as $action => $allowed) {
				$this->request->params['controller'] = $resource['controller'];
				$this->request->params['prefix'] = $resource['prefix'];
				$this->request->params['action'] = $action;
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
			'autoClearCache' => true
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
			'auth_acl.ini'
		]);
		$this->assertTrue(is_array($res));

		$this->assertSame(['*' => 'moderator'], $res['Blogs.Blogs']);
		$this->assertSame(['index' => 'admin'], $res['Foo']);
	}

	/**
	 * Tests exception thrown when no acl.ini exists.
	 *
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testIniParsingMissingFileException() {
		$object = new TestTinyAuthorize($this->collection, [
			'autoClearCache' => true
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_parseFiles');
		$method->setAccessible(true);
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
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$expected = 'Tags';
		$res = $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$expected = 'admin/Tags';
		$res = $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$expected = 'Tags.Tags';
		$res = $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

		$expected = 'Tags.admin/Tags';
		$res = $method->invokeArgs($object, [$this->request]);
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
			'prefix' => null
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'tags';	// test incorrect casing
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test standard controller with /admin prefix
		$key = 'admin/Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => 'admin',
			'plugin' => null
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Admin/tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Admin/Tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test plugin controller without prefix
		$key = 'Tags.Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => null,
			'plugin' => 'Tags'
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
		$key = 'Tags.admin/Tags';
		$expected = [
			'controller' => 'Tags',
			'prefix' => 'admin',
			'plugin' => 'Tags'
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

		$key = 'Tags.Admin/Tags';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

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
			'rolesTable' => 'Roles'
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);

		// Test against roles array in Configure
		$expected = [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3
		];
		$res = $method->invoke($object);
		$this->assertEquals($expected, $res);

		// Test against roles from database
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'DatabaseRoles'
		]);
		$expected = [
			'user' => 11,
			'moderator' => 12,
			'admin' => 13
		];
		$res = $method->invoke($object);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests exception thrown when no roles are in Configure AND the roles
	 * database table does not exist.
	 *
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testAvailableRolesMissingTableException() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'NonExistentTable'
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);
		$method->invoke($object);
	}

	/**
	 * Tests exception thrown when the roles database table exists but contains
	 * no roles/records.
	 *
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testAvailableRolesEmptyTableException() {
		$object = new TestTinyAuthorize($this->collection, [

			'rolesTable' => 'EmptyRoles'
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getAvailableRoles');
		$method->setAccessible(true);
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
			'roleColumn' => 'role_id'
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

		// Single-role: get role id from roleColumn in user table
		$user = ['role_id' => 1];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertSame(['user' => 1], $res);

		// Multi-role: lookup roles directly in pivot table
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'roleColumn' => 'database_role_id',
		]);
		$user = ['id' => 2];
		$expected = [
			'user' => 11,
			'admin' => 13
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
			'admin' => 13
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
			'profile_id' => 2
		];
		$expected = [
			'user' => 11,
			'admin' => 13
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);

		$user = [
			'id' => 1,
			'profile_id' => 1
		];
		$expected = [
			'user' => 11,
			'moderator' => 12
		];
		$res = $method->invokeArgs($object, [$user]);
		$this->assertEquals($expected, $res);

		//without id
		$user = [
			'profile_id' => 1
		];
		$expected = [
			'user' => 11,
			'moderator' => 12
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
		$this->request->params['action'] = 'any_action';
		$this->request->params['controller'] = 'AnyControllers';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;
		// Single role
		$object = new TestTinyAuthorize($this->collection, [
			'superAdmin' => 1,
		]);

		$user = ['id' => 1, 'role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 2, 'role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'group_id',
			'superAdmin' => 1
		]);
		$user = ['id' => 100, 'role_id' => 1, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => 1, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'any_id_column',
			'superAdminColumn' => 'group_id',
			'superAdmin' => 1
		]);
		$user = ['id' => 100, 'role_id' => 1, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => 1, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn without idColumn
		$object = new TestTinyAuthorize($this->collection, [
			'superAdminColumn' => 'group_id',
			'superAdmin' => 1
		]);
		$user = ['id' => 100, 'role_id' => 1, 'group_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => 1, 'group_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//single role and use superAdminColumn (string)
		$object = new TestTinyAuthorize($this->collection, [
			'idColumn' => 'any_id_column',
			'superAdminColumn' => 'group',
			'superAdmin' => 'Admin'
		]);
		$user = ['id' => 100, 'role_id' => 1, 'group' => 'admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['id' => 100, 'role_id' => 1, 'group' => 'Admin'];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['id' => 101, 'role_id' => 1, 'group' => 'authors'];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		//multi role
		$object = new TestTinyAuthorize($this->collection, [
			'multiRole' => true,
			'rolesTable' => 'DatabaseRoles',
			'pivotTable' => 'DatabaseUserRoles',
			'roleColumn' => 'role_id',
			'userColumn' => 'user_id',
			'superAdmin' => 1
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
			'superAdmin' => 1
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
			'superAdmin' => 100
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
			'superAdmin' => 100
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
			'superAdmin' => 'Admin'
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
	 * @expectedException \Cake\Core\Exception\Exception
	 * @return void
	 */
	public function testUserRolesMissingRoleColumn() {
		$object = new TestTinyAuthorize($this->collection, [
			'rolesTable' => 'NonExistentTable',
			'multiRole' => false
		]);

		// Make protected function available
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_getUserRoles');
		$method->setAccessible(true);

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
			'multiRole' => true
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
