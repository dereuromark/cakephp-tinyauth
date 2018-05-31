<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::reload();

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks(DashedRoute::class);
});
