<?php

namespace TinyAuth\Test\TestCase\Middleware;

use Authorization\AuthorizationService;
use Authorization\Exception\ForbiddenException;
use Authorization\IdentityInterface;
use Authorization\Policy\MapResolver;
use Authorization\Policy\Result;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

class RequestAuthorizationMiddlewareTest extends TestCase {

	public function testPolicyExceptionUsesConfiguredUnauthorizedHandler(): void {
		$policy = new class {
			public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool {
				throw new ForbiddenException(new Result(false, 'policy-denial'));
			}
		};
		$request = $this->request($policy);
		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->expects($this->never())->method('handle');
		$middleware = $this->middleware(['unauthorizedHandler' => ['className' => 'TinyAuth.ForbiddenRedirect', 'url' => '/denied', 'unauthorizedMessage' => false]]);
		$response = $middleware->process($request, $handler);
		$this->assertSame(302, $response->getStatusCode());
		$this->assertSame('/denied?redirect=%2Fprivate', $response->getHeaderLine('Location'));
	}

	public function testDeniedPolicyResultIsPreserved(): void {
		$result = new Result(false, 'original-policy-reason');
		$policy = new class ($result) {
			public function __construct(private Result $result) {
			}

			public function canAccess(?IdentityInterface $identity, ServerRequest $request): Result {
				return $this->result;
			}
		};
		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->expects($this->never())->method('handle');
		try {
			$this->middleware()->process($this->request($policy), $handler);
			$this->fail('Expected policy denial.');
		} catch (ForbiddenException $exception) {
			$this->assertSame($result, $exception->getResult());
		}
	}

	public function testPublicActionSkipsPolicyCheckAndMarksAuthorization(): void {
		$policy = new class {
			public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool {
				throw new ForbiddenException();
			}
		};
		$request = $this->request($policy)->withParam('controller', 'Users')->withParam('action', 'index');
		$handler = $this->createMock(RequestHandlerInterface::class);
		$handler->expects($this->once())->method('handle')->with($request)->willReturn(new Response(['body' => 'public']));
		$response = $this->middleware()->process($request, $handler);
		$this->assertSame('public', (string)$response->getBody());
		$this->assertTrue($request->getAttribute('authorization')->authorizationChecked());
	}

	private function request(object $policy): ServerRequest {
		$resolver = new MapResolver();
		$resolver->map(ServerRequest::class, $policy);

		return (new ServerRequest(['url' => '/private', 'params' => ['controller' => 'Tags', 'action' => 'delete', 'prefix' => null, 'plugin' => null]]))->withAttribute('authorization', new AuthorizationService($resolver));
	}

	private function middleware(array $options = []): RequestAuthorizationMiddleware {
		return new RequestAuthorizationMiddleware($options + ['allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS, 'autoClearCache' => true]);
	}

}
