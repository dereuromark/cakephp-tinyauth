<?php
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::reload();

Router::scope('/', function($routes) {
	$routes->fallbacks(DashedRoute::class);
});
