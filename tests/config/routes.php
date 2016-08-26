<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;

Router::reload();

Router::scope('/', function($routes) {
    $routes->fallbacks(DashedRoute::class);
});
