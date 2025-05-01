<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Exception;

class OffersController extends Controller {

	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		$this->Auth->deny(['denied']);
	}

	/**
	 * @return void
	 */
	public function index() {
	}

	/**
	 * @throws \Exception
	 * @return void
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
