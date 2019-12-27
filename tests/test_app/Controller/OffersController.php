<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Exception;

class OffersController extends Controller {

	/**
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeFilter(EventInterface $event) {
		$this->Auth->deny(['denied']);
	}

	/**
	 * @return void
	 */
	public function index() {
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function denied() {
		throw new Exception('Should not be reached!');
	}

	/**
	 * @return void
	 */
	public function superPrivate() {
	}

}
