<?php

namespace TinyAuth\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use TinyAuth\Utility\Config;

/**
 * Adapter for checking role-based controller permissions directly.
 *
 * CakePHP 5 applications normally use the TinyAuth Authorization component or
 * RequestPolicy with the official Authorization plugin. This adapter exposes
 * the same ACL check through authorize().
 */
class TinyAuthorize extends BaseAuthorize {

	use AclTrait;
	use AllowTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array<string, mixed> $config
	 * @throws \Cake\Core\Exception\CakeException
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);
	}

	/**
	 * Authorizes a user using the AclComponent.
	 *
	 * Allows single or multi role based authorization
	 *
	 * Examples:
	 * - User HABTM Roles (Role array in User array)
	 * - User belongsTo Roles (role_id in User array)
	 *
	 * @param \ArrayObject|array $user The user to authorize
	 * @param \Cake\Http\ServerRequest $request The request needing authorization.
	 * @return bool Success
	 */
	public function authorize($user, ServerRequest $request): bool {
		return $this->_checkUser((array)$user, $request->getAttribute('params'));
	}

}
