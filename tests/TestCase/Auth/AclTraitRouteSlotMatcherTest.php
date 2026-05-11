<?php

namespace TinyAuth\Test\TestCase\Auth;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;

/**
 * Direct coverage for the route-slot matcher helpers extracted in #169.
 *
 * The matchers are protected on the traits, so we reach them via a tiny
 * anonymous host class plus reflection — simpler than wiring a full
 * TinyAuthorize integration for what is essentially a pure-function check.
 */
class AclTraitRouteSlotMatcherTest extends TestCase {

	protected object $aclHost;

	protected object $allowHost;

	public function setUp(): void {
		parent::setUp();

		$this->aclHost = new class {

			use AclTrait;

			public function call(mixed $request, mixed $rule): bool {
				return $this->_matchesRouteSlot($request, $rule);
			}

		};

		$this->allowHost = new class {

			use AllowTrait;

			public function call(mixed $request, mixed $rule): bool {
				return $this->_matchesAllowSlot($request, $rule);
			}

		};
	}

	/**
	 * @return array<array{mixed, mixed, bool}>
	 */
	public static function slotProvider(): array {
		return [
			// Both empty → match (no plugin/prefix on either side)
			[null, null, true],
			[null, '', true],
			['', null, true],
			['', '', true],
			// Only one side empty → no match (we are protecting against the
			// old !empty()-mixed-with-isset() confusion)
			[null, 'Admin', false],
			['Admin', null, false],
			['', 'Admin', false],
			['Admin', '', false],
			// Both populated → strict equality (no loose juggling)
			['Admin', 'Admin', true],
			['Admin', 'Public', false],
			['0', '0', true],
			['0', 0, false], // distinct types must not collapse
		];
	}

	/**
	 * @param mixed $request
	 * @param mixed $rule
	 * @param bool $expected
	 * @return void
	 */
	#[DataProvider('slotProvider')]
	public function testAclMatcher(mixed $request, mixed $rule, bool $expected): void {
		$this->assertSame($expected, $this->aclHost->call($request, $rule));
	}

	/**
	 * @param mixed $request
	 * @param mixed $rule
	 * @param bool $expected
	 * @return void
	 */
	#[DataProvider('slotProvider')]
	public function testAllowMatcher(mixed $request, mixed $rule, bool $expected): void {
		$this->assertSame($expected, $this->allowHost->call($request, $rule));
	}

	/**
	 * Reflection sanity check: the protected method is still named and visible
	 * for downstream subclasses that may want to override matching semantics.
	 *
	 * @return void
	 */
	public function testMatcherIsProtectedAndOverridable(): void {
		$ref = new ReflectionMethod($this->aclHost, '_matchesRouteSlot');
		$this->assertTrue($ref->isProtected());
		$this->assertFalse($ref->isFinal());
	}

}
