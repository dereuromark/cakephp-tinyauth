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
 * Test case for TinyAuth Authentication
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

		$this->Collection = new ComponentRegistry();

		$this->request = new Request();

		$aclData = <<<INI
; ----------------------------------------------------------
; TagsController (no prefixed route, no plugin)
; ----------------------------------------------------------
[Tags]
index = user, undefined-role
edit = user
delete = admin
very_long_underscored_action = user
veryLongActionNameAction = user
public_action = public
; ----------------------------------------------------------
; TagsController (/admin prefixed route, no plugin)
; ----------------------------------------------------------
[admin/Tags]
index = user, undefined-role
edit = user
delete = admin
very_long_underscored_action = user
veryLongActionNameAction = user
public_action = public
; ----------------------------------------------------------
; TagsController (plugin Tags, no prefixed route)
; ----------------------------------------------------------
[Tags.Tags]
index = user
edit,view = user
delete = admin
public_action = public
very_long_underscored_action = user
veryLongActionNameAction = user
; ----------------------------------------------------------
; TagsController (plugin Tags, /admin prefixed route)
; ----------------------------------------------------------
[Tags.admin/Tags]
index = user
view, edit = user
delete = admin
public_action = public
very_long_underscored_action = user
veryLongActionNameAction = user
; ----------------------------------------------------------
; CommentsController, used for testing 'allowUser' access to
; non-admin-prefixed routes.
; ----------------------------------------------------------
[special/Comments]
* = admin
[Comments.special/Comments]
* = admin
; ----------------------------------------------------------
; PostsController, used for testing generic wildcard access
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
; BlogsController, used for testing specific wildcard access
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
			'Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => null,
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'admin',
				'plugin' => null,
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'Tags.Tags' => [
				'controller' => 'Tags',
				'prefix' => null,
				'plugin' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'Tags.admin/Tags' => [
				'controller' => 'Tags',
				'prefix' => 'admin',
				'plugin' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_underscored_action' => [1],
					'veryLongActionNameAction' => [1]
				]
			],
			'special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'special',
				'plugin' => null,
				'actions' => [
					'*' => [3]
				]
			],
			'Comments.special/Comments' => [
				'controller' => 'Comments',
				'prefix' => 'special',
				'plugin' => 'Comments',
				'actions' => [
					'*' => [3]
				]
			],
			'Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => null,
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'admin',
				'plugin' => null,
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Posts.Posts' => [
				'controller' => 'Posts',
				'prefix' => null,
				'plugin' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Posts.admin/Posts' => [
				'controller' => 'Posts',
				'prefix' => 'admin',
				'plugin' => 'Posts',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => null,
				'actions' => [
					'*' => [2]
				]
			],
			'admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'admin',
				'plugin' => null,
				'actions' => [
					'*' => [2]
				]
			],
			'Blogs.Blogs' => [
				'controller' => 'Blogs',
				'prefix' => null,
				'plugin' => 'Blogs',
				'actions' => [
					'*' => [2]
				]
			],
			'Blogs.admin/Blogs' => [
				'controller' => 'Blogs',
				'prefix' => 'admin',
				'plugin' => 'Blogs',
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
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true
		]);
		$this->assertEquals('Roles', $object->config('aclTable'));
		$this->assertEquals('role_id', $object->config('aclKey'));

		// All tests performed against this action
		$this->request->params['action'] = 'add';

		// Test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 4]; // invalid non-existing role
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1]; // valid role without authorization
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

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
		$object = new TestTinyAuthorize($this->Collection, [
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
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true]
		);

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
		$object = new TestTinyAuthorize($this->Collection, [
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
		$object = new TestTinyAuthorize($this->Collection, [
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
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true
		]);

		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'delete';

		// Flat list of roles
		$user = [
			'Roles' => [2, 4]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = [
			'Roles' => [1, 3]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// Verbose role definition using the new 2.x contain param for Auth
		$user = [
			'Roles' => [
				['id' => 2, 'RoleUsers' => []],
				['id' => 4, 'RoleUsers' => []]
			],
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

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

		// All tests performed against this action
		$this->request->params['action'] = 'any_action';

		// Test standard controller
		$this->request->params['controller'] = 'Posts';
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
	* but combines it with a specific group (here: * = moderators).
	*
	* @return void
	*/
	public function testBasicUserMethodAllowedWildcardSpecificGroup() {
		$object = new TestTinyAuthorize($this->Collection, [
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
	 * Tests with configuration setting 'allowUser' set to true, giving user
	 * access to all controller/actions except when prefixed with /admin
	 *
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$object = new TestTinyAuthorize($this->Collection, [
			'allowUser' => true,
			'autoClearCache' => true,
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
	 * TinyAuthorizeTest::testWithRoleTable()
	 *
	 * @return void
	 */
	public function testWithRoleTable() {
		$Users = TableRegistry::get('Users');
		$Users->belongsTo('Roles');

		// We want the session to be used.
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true
		]);

		// test standard controller
		$this->request->params['controller'] = 'Tags';
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
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = null;

		$expected = 'Tags';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test standard controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = null;

		$expected = 'admin/Tags';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = null;
		$this->request->params['plugin'] = 'Tags';

		$expected = 'Tags.Tags';
		$res =  $method->invokeArgs($object, [$this->request]);
		$this->assertEquals($expected, $res);

		// Test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['plugin'] = 'Tags';

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
