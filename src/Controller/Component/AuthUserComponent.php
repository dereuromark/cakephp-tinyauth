<?php

namespace TinyAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AuthUserTrait;

/**
 * TinyAuth component to handle all authorization.
 *
 * @property \Cake\Controller\Component\AuthComponent $Auth
 */
class AuthUserComponent extends Component {

	use AclTrait, AuthUserTrait;

	/**
	 * @var array
	 */
	public $components = ['Auth'];

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
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeRender(Event $event) {
		$controller = $event->subject();

		$authUser = $this->_getUser();
		$controller->set('_authUser', $authUser);
	}

	/**
	 * @param array $url
	 * @return bool
	 */
	public function hasAccess(array $url) {
		$url += [
			'prefix' => !empty($this->request->params['prefix']) ? $this->request->params['prefix'] : null,
			'plugin' => !empty($this->request->params['plugin']) ? $this->request->params['plugin'] : null,
			'controller' => $this->request->params['controller'],
			'action' => 'index',
		];

		return $this->_check((array)$this->Auth->user(), $url);
	}

	/**
	 * @return array
	 */
	protected function _getUser() {
		return (array)$this->Auth->user();
	}

}
