<?php

namespace TinyAuth\Controller\Component;

use ArrayAccess;
use Authentication\Identity;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\EventInterface;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Auth\AuthUserTrait;
use TinyAuth\Utility\Config;

/**
 * Easy access to the current logged-in user and the corresponding auth data.
 */
class AuthUserComponent extends Component {

	use AclTrait;
	use AllowTrait;
	use AuthUserTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array<string, mixed> $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		/** @var \Cake\Controller\Controller $controller */
		$controller = $event->getSubject();

		$authUser = $this->_getUser();
		$controller->set('_authUser', $authUser);
	}

	/**
	 * @return \ArrayAccess|array|null
	 */
	public function identity(): ArrayAccess|array|null {
		/** @var \Authorization\Identity|\Authentication\Identity|null $identity */
		$identity = $this->getController()->getRequest()->getAttribute('identity');
		if (!$identity) {
			return null;
		}

		/** @var \ArrayAccess|array|null */
		return $identity->getOriginalData();
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

		return $this->_checkUser($this->_getUser(), $url);
	}

	/**
	 * @return array
	 */
	protected function _getUser(): array {
		if (class_exists(Identity::class)) {
			$identity = $this->identity();

			return $identity ? $this->_toArray($identity) : [];
		}

		// We skip for new plugin(s)
		if ($this->getController()->components()->has('Authentication')) {
			return [];
		}

		// Fallback to old Auth style
		if (!$this->getController()->components()->has('Auth')) {
			$this->getController()->loadComponent('TinyAuth.Auth');
		}

		/** @phpstan-ignore property.notFound */
		return (array)$this->getController()->Auth->user();
	}

}
