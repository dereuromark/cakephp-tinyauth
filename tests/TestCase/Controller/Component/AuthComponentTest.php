<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use TinyAuth\Controller\Component\AuthComponent;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 */
class AuthComponentTest extends TestCase {

    /**
     * @var AuthComponent
     */
    protected $AuthComponent;

    /**
     * @var
     */
    protected $componentConfig = [];
    /**
     * @return void
     */
    public function setUp() {
        $this->componentConfig = [
            'authPath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS
        ];
    }

    /**
     * @return void
     */
    public function testValid() {
        $request = new Request(['params' => [
            'controller' => 'Users',
            'action' => 'view',
            'plugin' => null,
            '_ext' => null,
            'pass' => [1]
        ]]);
        $controller = $this->getControllerMock($request);

        $registry = new ComponentRegistry($controller);
        $this->AuthComponent = new AuthComponent($registry, $this->componentConfig);

        $config = [];
        $this->AuthComponent->initialize($config);

        $event = new Event('Controller.startup', $controller);
        $response = $this->AuthComponent->startup($event);
        $this->assertNull($response);
    }

    /**
     * @return void
     */
    public function testInvalid() {
        $request = new Request(['params' => [
            'controller' => 'FooBar',
            'action' => 'index',
            'plugin' => null,
            '_ext' => null,
            'pass' => []
        ]]);
        $controller = $this->getControllerMock($request);

        $registry = new ComponentRegistry($controller);
        $this->AuthComponent = new AuthComponent($registry, $this->componentConfig);

        $config = [];
        $this->AuthComponent->initialize($config);

        $event = new Event('Controller.startup', $controller);
        $response = $this->AuthComponent->startup($event);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->statusCode());
    }

    /**
     * @param \Cake\Network\Request $request
     * @return Controller
     */
    protected function getControllerMock(Request $request) {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs([$request])
            ->setMethods(['isAction'])
            ->getMock();

        $controller->expects($this->once())->method('isAction')->willReturn(true);

        return $controller;
    }

}
