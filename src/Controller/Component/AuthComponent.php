<?php

namespace TinyAuth\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Event\Event;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * TinyAuth AuthComponent to handle all authentication in a central INI file.
 *
 * @property \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AuthComponent extends CakeAuthComponent {

	use AclTrait;
	use AllowTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);
	}

	/**
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		$params = $this->_registry->getController()->getRequest()->getAttribute('params');
		$this->_prepareAuthentication($params);
	}

	/**
	 * Events supported by this component.
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return [
			'Controller.beforeRender' => 'beforeRender',
		] + parent::implementedEvents();
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeRender(Event $event) {
		/** @var \Cake\Controller\Controller $controller */
		$controller = $event->getSubject();

		$authUser = (array)$this->user();
		$controller->set('_authUser', $authUser);
	}

	/**
	 * @param array $params
	 * @return void
	 */
	protected function _prepareAuthentication(array $params) {
		$rule = $this->_getAllowRule($params);
		if (!$rule) {
			return;
		}

		if (in_array('*', $rule['allow'], true)) {
			$this->allow();
		} elseif (!empty($rule['allow'])) {
			$this->allow($rule['allow']);
		}
		if (in_array('*', $rule['deny'], true)) {
			$this->deny();
		} elseif (!empty($rule['deny'])) {
			$this->deny($rule['deny']);
		}
	}

}
