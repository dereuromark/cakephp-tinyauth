<?php

namespace TinyAuth\Test\TestCase\Utility;

use Authorization\AuthorizationService;
use Authorization\IdentityDecorator;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use stdClass;
use TinyAuth\Controller\Component\AuthenticationComponent;
use TinyAuth\Policy\RequestPolicy;
use TinyAuth\Utility\Cache;
use TinyAuth\View\Helper\AuthenticationHelper;

class RuleCacheContextTest extends TestCase {

	protected string $path;

	public function setUp(): void {
		parent::setUp();
		Configure::write('debug', false);
		Configure::write('Roles', ['user' => 1, 'admin' => 2]);
		Cache::clear();
		$this->path = TMP . 'rule-cache-context' . DS;
		if (!is_dir($this->path)) {
			mkdir($this->path);
		}
		file_put_contents($this->path . 'public.ini', 'Users = index');
		file_put_contents($this->path . 'private.ini', 'Users = "!index"');
		file_put_contents($this->path . 'user.ini', "[Users]\nindex = user");
		file_put_contents($this->path . 'admin.ini', "[Users]\nindex = admin");
	}

	public function tearDown(): void {
		Cache::clear();
		foreach (glob($this->path . '*.ini') ?: [] as $file) {
			unlink($file);
		}
		rmdir($this->path);
		parent::tearDown();
	}

	public function testDifferentAllowFilesCannotSharePublicPermissions(): void {
		$request = $this->request();
		$registry = new ComponentRegistry(new Controller($request));
		$public = new AuthenticationComponent($registry, ['allowFilePath' => $this->path, 'allowFile' => 'public.ini']);
		$private = new AuthenticationComponent($registry, ['allowFilePath' => $this->path, 'allowFile' => 'private.ini']);

		$this->assertTrue($public->isPublic());
		$this->assertFalse($private->isPublic());
		$this->assertTrue($public->isPublic());
	}

	public function testComponentAndHelperReuseTheSameFileCacheUntilCleared(): void {
		$request = $this->request();
		$config = ['allowFilePath' => $this->path, 'allowFile' => 'public.ini'];
		$component = new AuthenticationComponent(new ComponentRegistry(new Controller($request)), $config);
		$this->assertTrue($component->isPublic());

		file_put_contents($this->path . 'public.ini', 'Users = "!index"');
		$view = new View();
		$view->setRequest($request);
		$helper = new AuthenticationHelper($view, $config);
		$this->assertTrue($helper->isPublic());
		Cache::clear();
		$this->assertFalse($helper->isPublic());
	}

	public function testDifferentAclFilesCannotShareRolePermissions(): void {
		$identity = new IdentityDecorator($this->createStub(AuthorizationService::class), ['id' => 1, 'role_id' => 1]);
		$user = new RequestPolicy(['aclFilePath' => $this->path, 'aclFile' => 'user.ini']);
		$admin = new RequestPolicy(['aclFilePath' => $this->path, 'aclFile' => 'admin.ini']);

		$this->assertTrue($user->canAccess($identity, $this->request()));
		$this->assertFalse($admin->canAccess($identity, $this->request()));
		$this->assertTrue($user->canAccess($identity, $this->request()));
	}

	public function testChangedRoleMapCannotReuseOldRoleIds(): void {
		$identity = new IdentityDecorator($this->createStub(AuthorizationService::class), ['id' => 1, 'role_id' => 1]);
		$first = new RequestPolicy(['aclFilePath' => $this->path, 'aclFile' => 'user.ini']);
		$this->assertTrue($first->canAccess($identity, $this->request()));

		Configure::write('Roles', ['guest' => 1, 'user' => 2]);
		$second = new RequestPolicy(['aclFilePath' => $this->path, 'aclFile' => 'user.ini']);
		$this->assertFalse($second->canAccess($identity, $this->request()));
	}

	public function testContextKeepsPublicCacheReadAndClearContract(): void {
		$rules = ['Users' => ['allow' => ['index']]];
		Cache::write(Cache::KEY_ALLOW, $rules, 'first');
		$this->assertSame($rules, Cache::read(Cache::KEY_ALLOW));
		$this->assertSame($rules, Cache::read(Cache::KEY_ALLOW, 'first'));
		$this->assertNull(Cache::read(Cache::KEY_ALLOW, 'second'));
		Cache::clear();
		$this->assertNull(Cache::read(Cache::KEY_ALLOW, 'first'));
	}

	public function testLegacyUnscopedValuesAreNotAcceptedAsScopedRules(): void {
		$rules = ['Users' => ['allow' => ['index']]];
		Cache::write(Cache::KEY_ALLOW, $rules);
		$this->assertSame($rules, Cache::read(Cache::KEY_ALLOW));
		$this->assertNull(Cache::read(Cache::KEY_ALLOW, 'first'));
	}

	public function testObjectConfigurationBypassesFingerprinting(): void {
		$this->assertNull(Cache::fingerprint(['callback' => static fn () => true]));
		$this->assertNull(Cache::fingerprint(['options' => ['service' => new stdClass()]]));
	}

	private function request(): ServerRequest {
		return new ServerRequest([
			'params' => [
				'controller' => 'Users',
				'action' => 'index',
				'plugin' => null,
				'prefix' => null,
			],
		]);
	}

}
