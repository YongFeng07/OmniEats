<?php

/**
 * Route table for the front controller.
 *
 * Handler format: 'App\Controllers\Name@method'
 * Guard format (third argument): see App\Middleware\AuthMiddleware —
 *   'guest' | 'auth' | 'roles:super_admin,restaurant_owner'
 */

$router->get('/', 'App\Controllers\HomeController@index');

// Authentication (Module 1)
$router->get('/login', 'App\Controllers\AuthController@showLogin', ['guest']);
$router->post('/login', 'App\Controllers\AuthController@login', ['guest']);
$router->post('/logout', 'App\Controllers\AuthController@logout', ['auth']);

// Role dashboards
$router->get('/dashboard', 'App\Controllers\DashboardController@index', ['auth']);
$router->get('/admin', 'App\Controllers\DashboardController@admin', ['roles:super_admin']);
$router->get('/owner', 'App\Controllers\DashboardController@owner', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/customer', 'App\Controllers\DashboardController@customer', ['roles:customer']);
