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

// Registration (Module 1) — role is fixed per route, never chosen freely
$router->get('/register', 'App\Controllers\RegisterController@showCustomer', ['guest']);
$router->post('/register', 'App\Controllers\RegisterController@customer', ['guest']);
$router->get('/register/restaurant', 'App\Controllers\RegisterController@showOwner', ['guest']);
$router->post('/register/restaurant', 'App\Controllers\RegisterController@owner', ['guest']);

// Profile & password (Module 1, all logged-in users)
$router->get('/profile', 'App\Controllers\ProfileController@show', ['auth']);
$router->post('/profile', 'App\Controllers\ProfileController@update', ['auth']);
$router->get('/profile/password', 'App\Controllers\ProfileController@showPassword', ['auth']);
$router->post('/profile/password', 'App\Controllers\ProfileController@changePassword', ['auth']);

// Admin user management (Module 1) — static routes BEFORE {id} routes
$router->get('/admin/users', 'App\Controllers\Admin\UserController@index', ['roles:super_admin']);
$router->get('/admin/users/create', 'App\Controllers\Admin\UserController@create', ['roles:super_admin']);
$router->post('/admin/users/create', 'App\Controllers\Admin\UserController@store', ['roles:super_admin']);
$router->get('/admin/users/{id}', 'App\Controllers\Admin\UserController@show', ['roles:super_admin']);
$router->get('/admin/users/{id}/edit', 'App\Controllers\Admin\UserController@edit', ['roles:super_admin']);
$router->post('/admin/users/{id}/edit', 'App\Controllers\Admin\UserController@update', ['roles:super_admin']);
$router->post('/admin/users/{id}/toggle-status', 'App\Controllers\Admin\UserController@toggleStatus', ['roles:super_admin']);

// Public restaurant browsing (Module 2) — approved tenants only
$router->get('/restaurants', 'App\Controllers\RestaurantController@index');
$router->get('/restaurants/{id}', 'App\Controllers\RestaurantController@show');

// Owner restaurant management (Module 2) — tenant from session
$router->get('/owner/restaurant/create', 'App\Controllers\Owner\RestaurantController@create', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/create', 'App\Controllers\Owner\RestaurantController@store', ['roles:restaurant_owner']);
$router->get('/owner/restaurant/edit', 'App\Controllers\Owner\RestaurantController@edit', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/edit', 'App\Controllers\Owner\RestaurantController@update', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/toggle-open', 'App\Controllers\Owner\RestaurantController@toggleOpen', ['roles:restaurant_owner']);

// Owner branch management (Module 2)
$router->get('/owner/branches', 'App\Controllers\Owner\BranchController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/branches/create', 'App\Controllers\Owner\BranchController@create', ['roles:restaurant_owner']);
$router->post('/owner/branches/create', 'App\Controllers\Owner\BranchController@store', ['roles:restaurant_owner']);
$router->get('/owner/branches/{id}/edit', 'App\Controllers\Owner\BranchController@edit', ['roles:restaurant_owner']);
$router->post('/owner/branches/{id}/edit', 'App\Controllers\Owner\BranchController@update', ['roles:restaurant_owner']);
$router->post('/owner/branches/{id}/delete', 'App\Controllers\Owner\BranchController@delete', ['roles:restaurant_owner']);

// Admin restaurant management (Module 2) — approval lifecycle
$router->get('/admin/restaurants', 'App\Controllers\Admin\RestaurantController@index', ['roles:super_admin']);
$router->get('/admin/restaurants/{id}', 'App\Controllers\Admin\RestaurantController@show', ['roles:super_admin']);
$router->post('/admin/restaurants/{id}/approve', 'App\Controllers\Admin\RestaurantController@approve', ['roles:super_admin']);
$router->post('/admin/restaurants/{id}/toggle-status', 'App\Controllers\Admin\RestaurantController@toggleStatus', ['roles:super_admin']);

// Role dashboards
$router->get('/dashboard', 'App\Controllers\DashboardController@index', ['auth']);
$router->get('/admin', 'App\Controllers\DashboardController@admin', ['roles:super_admin']);
$router->get('/owner', 'App\Controllers\DashboardController@owner', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/customer', 'App\Controllers\DashboardController@customer', ['roles:customer']);
