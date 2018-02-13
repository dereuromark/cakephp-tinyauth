<?php
namespace TinyAuth\Auth;

use Cake\Auth\BaseAuthorize;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;

/**
 * Probably the most simple and fastest ACL out there.
 * Only one config file `acl.ini` necessary,
 * doesn't even need a Roles Table / roles table.
 * Uses most persistent _cake_core_ cache by default.
 *
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 *
 * Usage:
 * Include it in your beforeFilter() method of the AppController with the following config:
 * 'authorize' => ['Tools.Tiny']
 *
 * Or with admin prefix protection only
 * 'authorize' => ['Tools.Tiny' => ['allowUser' => true]];
 *
 * @author Mark Scherer
 * @license MIT
 */
class TinyAuthorize extends BaseAuthorize {

	use AclTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config = $this->_prepareConfig($config);

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
	 * @param array|\ArrayObject $user The user to authorize
	 * @param \Cake\Http\ServerRequest $request The request needing authorization.
	 * @return bool Success
	 */
	public function authorize($user, ServerRequest $request) {
		return $this->_check((array)$user, $request->params);
	}

}
