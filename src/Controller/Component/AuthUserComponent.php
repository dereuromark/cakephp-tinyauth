<?php

namespace TinyAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventInterface;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Auth\AuthUserTrait;
use TinyAuth\Utility\Config;

/**
 * Easy access to the current logged-in user and the corresponding auth data.
 *
 * @property \TinyAuth\Controller\Component\AuthComponent $Auth
 */
class AuthUserComponent extends Component {

	use AclTrait;
	use AllowTrait;
	use AuthUserTrait;

	/**
	 * @var array
	 */
	protected array $components = ['TinyAuth.Auth'];

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeRender(EventInterface $event) {
		/** @var \Cake\Controller\Controller $controller */
		$controller = $event->getSubject();

		$authUser = $this->_getUser();
		$controller->set('_authUser', $authUser);
	}

	/**
	 * This is only for usage with already logged in persons as this uses the ACL (not allow) data.
	 *
	 * @param array $url
	 * @return bool
	 */
	public function hasAccess(array $url): bool {
		$params = $this->getController()->getRequest()->getAttribute('params');
		$url += [
			'prefix' => !empty($params['prefix']) ? $params['prefix'] : null,
			'plugin' => !empty($params['plugin']) ? $params['plugin'] : null,
			'controller' => $params['controller'],
			'action' => 'index',
		];

		return $this->_checkUser((array)$this->Auth->user(), $url);
	}

	/**
	 * @return array
	 */
	protected function _getUser() {
		return (array)$this->Auth->user();
	}

}
