<?php
namespace TinyAuth\Test\Auth;

use Cake\Controller\Controller;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\TinyAuthorize;

/**
 * Test case for DirectAuthentication
 *
 */
class TinyAuthorizeTest extends TestCase {

	public $fixtures = ['core.users', 'core.auth_users', 'plugin.tiny_auth.roles'];

	public $Collection;

	public $request;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		//$config = \Cake\Datasource\ConnectionManager::config('test');
		//$this->assertNotEmpty($config, 'No test connection set up.');

		$this->Collection = new ComponentRegistry();

		$this->request = new Request();

		$aclData = <<<INI
; ----------------------------------------------------------
; Userscontroller (no prefixed route, no plugin)
; ----------------------------------------------------------
[Users]
index = user, undefined-role
edit = user
delete = admin
very_long_underscored_action = user
veryLongActionNameAction = user
public_action = public
; ----------------------------------------------------------
; UsersController (/admin prefixed route, no plugin)
; ----------------------------------------------------------
[admin/Users]
index = user, undefined-role
edit = user
delete = admin
public_action = public
very_long_underscored_action = user
veryLongActionNameAction = user
; ----------------------------------------------------------
; TagsController (no prefixed route, no plugin)
; ----------------------------------------------------------
[Tags]
* = *
; ----------------------------------------------------------
; TagsController (plugin Tags, no prefixed route)
; ----------------------------------------------------------
[Tags.Tags]
index = user
edit,view = user
* = admin
public_action = public
very_long_underscored_action = user
veryLongActionNameAction = user
; ----------------------------------------------------------
; TagsController (plugin Tags, /admin prefixed route)
; ----------------------------------------------------------
[Tags.admin/Tags]
index = user
edit,view = user
add = admin
public_action = public
very_long_underscored_action = user
veryLongActionNameAction = user
; ----------------------------------------------------------
; CommentsController (no plugin, /special prefixed route)
; ----------------------------------------------------------
[special/Comments]
* = admin
; ----------------------------------------------------------
; CommentsController (plugin Comments, /special prefixed route)
; ----------------------------------------------------------
[Comments.special/Comments]
* = admin
; ----------------------------------------------------------
; PostsController (for testing generic wildcard access)
; ----------------------------------------------------------
[Posts]
*=*
[admin/Posts]
* = *
[Posts.Posts]
* = *
[Posts.admin/Posts]
* = *
; ----------------------------------------------------------
; BlogsController (for testing specific wildcard access)
; ----------------------------------------------------------
[Blogs]
*= moderator
[admin/Blogs]
* = moderator
[Blogs.Blogs]
* = moderator
[Blogs.admin/Blogs]
* = moderator
INI;

		file_put_contents(TMP . 'acl.ini', $aclData);
		$this->assertTrue(file_exists(TMP . 'acl.ini'));

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
			'public' => -1
		]);
	}

	public function tearDown() {
		unlink(TMP . 'acl.ini');

		parent::tearDown();
	}

	/**
	 * Test applying config in the constructor
	 *
	 * @return void
	 */
	public function testConstructor() {
		$object = new TestTinyAuthorize($this->Collection, [
			'aclTable' => 'AuthRole',
			'aclKey' => 'auth_role_id',
			'autoClearCache' => true,
		]);
		$this->assertEquals('AuthRole', $object->config('aclTable'));
		$this->assertEquals('auth_role_id', $object->config('aclKey'));
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$res = $object->getAcl();

		$expected = [
			'Users' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Users',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'admin/Users' => [
				'plugin' => null,
				'prefix' => 'admin',
				'controller' => 'Users',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'Tags' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Tags',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Tags.Tags' => [
				'plugin' => 'Tags',
				'prefix' => null,
				'controller' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'*' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'Tags.admin/Tags' => [
				'plugin' => 'Tags',
				'prefix' => 'admin',
				'controller' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'add' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'special/Comments' => [
				'plugin' => null,
				'prefix' => 'special',
				'controller' => 'Comments',
				'actions' => [
					'*' => [3]
				]
			],
			'Comments.special/Comments' => [
				'plugin' => 'Comments',
				'prefix' => 'special',
				'controller' => 'Comments',
				'actions' => [
					'*' => [3]
				]
			],
			'Posts' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'admin/Posts' => [
				'plugin' => null,
				'prefix' => 'admin',
				'controller' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Posts.Posts' => [
				'plugin' => 'Posts',
				'prefix' => null,
				'controller' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Posts.admin/Posts' => [
				'plugin' => 'Posts',
				'prefix' => 'admin',
				'controller' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Blogs' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Blogs',
				'actions' => [
					'*' => [2]
				]
			],
			'admin/Blogs' => [
				'plugin' => null,
				'prefix' => 'admin',
				'controller' => 'Blogs',
				'actions' => [
					'*' => [2]
				]
			],
			'Blogs.Blogs' => [
				'plugin' => 'Blogs',
				'prefix' => null,
				'controller' => 'Blogs',
				'actions' => [
					'*' => [2]
				]
			],
			'Blogs.admin/Blogs' => [
				'plugin' => 'Blogs',
				'prefix' => 'admin',
				'controller' => 'Blogs',
				'actions' => [
					'*' => [2]
				]
			]
		];
		//debug($res);
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodDisallowed() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$this->assertEquals('Roles', $object->config('aclTable'));
		$this->assertEquals('role_id', $object->config('aclKey'));

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'add';
		$user = ['role_id' => 4]; // invalid non-existing role
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1]; // valid role without authorization
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'add';
		$this->request->params['prefix'] = null;

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'add';

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'edit';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['action'] = 'delete';
		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'edit';

		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['action'] = 'index';
		$this->request->params['prefix'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'add';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'index';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'add';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests using incorrect casing, enforces strict acl.ini definitions.
	 *
	 * @return void
	 */
	public function testCaseSensitivity() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Test incorrect controller casing
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'index';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect controller casing with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct controller casing with incorrect prefix casing
		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'Admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test incorrect plugin controller casing
		$this->request->params['controller'] = 'tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with incorrect plugin casing
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'tags';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test correct plugin controller with correct plugin but incorrect prefix casing
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = 'Admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'veryLongActionNameAction';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
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
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'very_long_underscored_action';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'delete';

		// Flat list of roles
		$user = [
			'Roles' => [1, 3]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Verbose role defition using the new 2.x contain param for Auth
		$user = [
			'Roles' => [
				['id' => 1, 'RoleUsers' => []],
				['id' => 3, 'RoleUsers' => []]
			],
		];

		$user = [
			'Roles' => [
				['id' => 1, 'RoleUsers' => []],
				['id' => 3, 'RoleUsers' => []]
			]
		];
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
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true
		]);

		// Test *=* for standard controller
		$this->request->params['controller'] = 'Posts';
		$this->request->params['action'] = 'any_action';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 123];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test *=* for standard controller with /admin prefix
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 123];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test *=* for plugin controller
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Posts';

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 123];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test *=* for plugin controller with /admin prefix
		$this->request->params['controller'] = 'Posts';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Posts';

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 123];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

	}

	/**
	* Tests access to a controller that uses the * wildcard for the action
	* but combines it with a specific group (e.g. * = moderators).
	*
	* @return void
	*/
	public function testBasicUserMethodAllowedWildcardSpecificGroup() {
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true
		]);
		$user = ['role_id' => 2];

		// test standard controller
		$this->request->params['controller'] = 'Blogs';
		$this->request->params['action'] = 'any_action';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controlller
		$this->request->params['plugin'] = 'Blogs';
		$this->request->params['prefix'] = null;
		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controlller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}


	/**
	 * Tests with configuration setting 'allowUser' set to true, giving user
	 * access to all controller/actions except when prefixed with /admin
	 *
	 * @todo: discuss logic before completing, see code L120
	 *
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$object = new TestTinyAuthorize($this->Collection, [
			'allowUser' => true,
			'autoClearCache' => true
		]);

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'unknown_action';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3]; // admin should be allowed
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->request->params['prefix'] = 'add';
		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Users should have access to standard controller using non-admin prefix
		$this->request->params['controller'] = 'Comments';
		$this->request->params['plugin'] = null;
		$this->request->params['prefix'] = 'special';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['prefix'] = 'index';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Users should have access to plugin controller using non-admin prefix
		$this->request->params['controller'] = 'Comments';
		$this->request->params['plugin'] = 'Comments';
		$this->request->params['prefix'] = 'special';
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
	 * Test with enabled configuration settings 'allowAdmin' and 'adminRole'
	 * giving users having the adminRole ID access to all actions that are
	 * prefixed using the 'adminPrefix' configuration setting.
	 *
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$config = [
			'allowAdmin' => true,
			'adminRole' => 3,
			'adminPrefix' => 'admin',
			'autoClearCache' => true
		];
		$object = new TestTinyAuthorize($this->Collection, $config);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'some_action';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * TinyAuthorizeTest::testWithRoleTable()
	 *
	 * @return void
	 */
	public function testWithRoleTable() {
		$Users = TableRegistry::get('Users');
		$Users->belongsTo('Roles');

		// We want the session to be used.
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'edit';

		// User role is 4 here, though. Also contains left joined Role date here just to check that it works, too.
		$user = [
			'Roles' => [
				'id' => '4',
				'alias' => 'user'
			],
			'role_id' => 4,
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'role_id' => 6
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Roles')));

		// Multi-role test - failure
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'Roles' => [
				['id' => 7, 'alias' => 'user'],
				['id' => 8, 'alias' => 'partner']
			]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Roles')));

		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Multi-role test
		$user = [
			'Roles' => [
				['id' => 4, 'alias' => 'user'],
				['id' => 6, 'alias' => 'partner'],
			]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests superAdmin role, allowed to all actions
	 *
	 * @return void
	 */
	public function testSuperAdminRole() {
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true,
			'superAdminRole' => 9
		]);
		$res = $object->getAcl();
		$user = [
			'role_id' => 9
		];

		foreach ($object->getAcl() as $resource) {
			foreach ($resource['actions'] as $action => $allowed) {
				$this->request->params['controller'] = $resource['controller'];
				$this->request->params['prefix'] = $resource['prefix'];
				$this->request->params['action'] = $action;
				$res = $object->authorize($user, $this->request);
				$this->assertTrue($res);
			}
		}
	}

	/**
	 * Tests constructing an ACL ini section key using CakeRequest parameters
	 *
	 * @return void
	 */
	public function testIniConstruct() {
		// Make protected function accessible
		$object = new TestTinyAuthorize($this->Collection);
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_constructIniKey');
		$method->setAccessible(true);

		// Test standard controller
		$this->request->params['controller'] = 'Users';
		$expected = 'Users';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$expected = 'admin/Users';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$expected = 'Tags.Tags';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$expected = 'Tags.admin/Tags';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * Tests deconstructing an ACL ini section key
	 *
	 * @return void
	 */
	public function testIniDeconstruct() {
		// Make protected function accessible
		$object = new TestTinyAuthorize($this->Collection);
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod('_deconstructIniKey');
		$method->setAccessible(true);

		// Test standard controller
		$key = 'Users';
		$expected = [
			'controller' => 'Users',
			'plugin' => null,
			'prefix' => null
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'users';	// test incorrect casing
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test standard controller with /admin prefix
		$key = 'admin/Users';
		$expected = [
			'controller' => 'Users',
			'plugin' => null,
			'prefix' => 'admin'
		];
		$res = $method->invokeArgs($object, [$key]);
		$this->assertEquals($expected, $res);

		$key = 'admin/users';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Admin/users';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		$key = 'Admin/Users';
		$res = $method->invokeArgs($object, [$key]);
		$this->assertNotEquals($expected, $res);

		// Test plugin controller without prefix
		$key = 'Tags.Tags';
		$expected = [
			'controller' => 'Tags',
			'plugin' => 'Tags',
			'prefix' => null
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
			'plugin' => 'Tags',
			'prefix' => 'admin'
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

}

class TestTinyAuthorize extends TinyAuthorize {

	public function matchArray() {
		return $this->_matchArray;
	}

	public function getAcl() {
		return $this->_getAcl();
	}

	protected function _getAcl($path = TMP) {
		return parent::_getAcl($path);
	}

	/**
	 * @return Cake\ORM\Table The User table
	 */
	public function getTable() {
		$Users = TableRegistry::get(CLASS_USER);
		$Users->belongsTo('Roles');

		return $Users;
	}

}
