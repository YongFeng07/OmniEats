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
$router->post('/profile/addresses', 'App\Controllers\ProfileController@addAddress', ['auth']);
$router->post('/profile/addresses/{id}/delete', 'App\Controllers\ProfileController@deleteAddress', ['auth']);
$router->post('/profile/addresses/{id}/default', 'App\Controllers\ProfileController@defaultAddress', ['auth']);

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
$router->post('/restaurants/{id}/favourite', 'App\Controllers\RestaurantController@toggleFavourite');

// Owner restaurant management (Module 2) — tenant from session
$router->get('/owner/restaurant/create', 'App\Controllers\Owner\RestaurantController@create', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/create', 'App\Controllers\Owner\RestaurantController@store', ['roles:restaurant_owner']);
$router->get('/owner/restaurant/edit', 'App\Controllers\Owner\RestaurantController@edit', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/edit', 'App\Controllers\Owner\RestaurantController@update', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/toggle-open', 'App\Controllers\Owner\RestaurantController@toggleOpen', ['roles:restaurant_owner']);
$router->post('/owner/restaurant/toggle-auto-accept', 'App\Controllers\Owner\RestaurantController@toggleAutoAccept', ['roles:restaurant_owner']);

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

// Admin platform activity overview (monitoring)
$router->get('/admin/activity', 'App\Controllers\Admin\ActivityController@index', ['roles:super_admin']);

// Voucher management (Promotion & Voucher — Student 1)
// Admin: full CRUD on every voucher; Owner: own restaurant's vouchers.
$router->get('/admin/vouchers', 'App\Controllers\Admin\VoucherController@index', ['roles:super_admin']);
$router->get('/admin/vouchers/create', 'App\Controllers\Admin\VoucherController@create', ['roles:super_admin']);
$router->post('/admin/vouchers/create', 'App\Controllers\Admin\VoucherController@store', ['roles:super_admin']);
$router->get('/admin/vouchers/{id}/edit', 'App\Controllers\Admin\VoucherController@edit', ['roles:super_admin']);
$router->post('/admin/vouchers/{id}/edit', 'App\Controllers\Admin\VoucherController@update', ['roles:super_admin']);
$router->post('/admin/vouchers/{id}/toggle', 'App\Controllers\Admin\VoucherController@toggle', ['roles:super_admin']);
$router->get('/owner/vouchers', 'App\Controllers\Owner\VoucherController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/vouchers/create', 'App\Controllers\Owner\VoucherController@create', ['roles:restaurant_owner']);
$router->post('/owner/vouchers/create', 'App\Controllers\Owner\VoucherController@store', ['roles:restaurant_owner']);
$router->get('/owner/vouchers/{id}/edit', 'App\Controllers\Owner\VoucherController@edit', ['roles:restaurant_owner']);
$router->post('/owner/vouchers/{id}/edit', 'App\Controllers\Owner\VoucherController@update', ['roles:restaurant_owner']);
$router->post('/owner/vouchers/{id}/toggle', 'App\Controllers\Owner\VoucherController@toggle', ['roles:restaurant_owner']);

// Public food item detail (Module 3)
$router->get('/foods/{id}', 'App\Controllers\FoodController@show');

// Owner menu management (Module 3) — owner + staff, tenant from session
$router->get('/owner/categories', 'App\Controllers\Owner\CategoryController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/categories/create', 'App\Controllers\Owner\CategoryController@create', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/categories/create', 'App\Controllers\Owner\CategoryController@store', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/categories/{id}/edit', 'App\Controllers\Owner\CategoryController@edit', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/categories/{id}/edit', 'App\Controllers\Owner\CategoryController@update', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/categories/{id}/delete', 'App\Controllers\Owner\CategoryController@delete', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/foods', 'App\Controllers\Owner\FoodItemController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/foods/create', 'App\Controllers\Owner\FoodItemController@create', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/foods/create', 'App\Controllers\Owner\FoodItemController@store', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/foods/{id}/edit', 'App\Controllers\Owner\FoodItemController@edit', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/foods/{id}/edit', 'App\Controllers\Owner\FoodItemController@update', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/foods/{id}/delete', 'App\Controllers\Owner\FoodItemController@delete', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/foods/{id}/toggle-available', 'App\Controllers\Owner\FoodItemController@toggleAvailable', ['roles:restaurant_owner,restaurant_staff']);

// Admin subscription plans (Module 4) — static routes BEFORE {id}
$router->get('/admin/plans', 'App\Controllers\Admin\PlanController@index', ['roles:super_admin']);
$router->get('/admin/plans/create', 'App\Controllers\Admin\PlanController@create', ['roles:super_admin']);
$router->post('/admin/plans/create', 'App\Controllers\Admin\PlanController@store', ['roles:super_admin']);
$router->get('/admin/plans/{id}/edit', 'App\Controllers\Admin\PlanController@edit', ['roles:super_admin']);
$router->post('/admin/plans/{id}/edit', 'App\Controllers\Admin\PlanController@update', ['roles:super_admin']);
$router->post('/admin/plans/{id}/toggle-active', 'App\Controllers\Admin\PlanController@toggleActive', ['roles:super_admin']);

// Admin subscription monitoring (Module 4)
$router->get('/admin/subscriptions', 'App\Controllers\Admin\SubscriptionController@index', ['roles:super_admin']);

// Owner subscription management (Module 4)
$router->get('/owner/subscription', 'App\Controllers\Owner\SubscriptionController@index', ['roles:restaurant_owner']);
$router->post('/owner/subscription/subscribe/{id}', 'App\Controllers\Owner\SubscriptionController@subscribe', ['roles:restaurant_owner']);
$router->post('/owner/subscription/cancel', 'App\Controllers\Owner\SubscriptionController@cancel', ['roles:restaurant_owner']);

// Shopping cart (Module 5) — customer only
$router->get('/cart', 'App\Controllers\CartController@show', ['roles:customer']);
$router->post('/cart/add', 'App\Controllers\CartController@add', ['roles:customer']);
$router->post('/cart/update', 'App\Controllers\CartController@update', ['roles:customer']);
$router->post('/cart/remove', 'App\Controllers\CartController@remove', ['roles:customer']);
$router->post('/cart/note', 'App\Controllers\CartController@note', ['roles:customer']);
$router->post('/cart/voucher', 'App\Controllers\CartController@voucher', ['roles:customer']);
$router->post('/cart/clear', 'App\Controllers\CartController@clear', ['roles:customer']);

// Checkout (Module 5)
$router->get('/checkout', 'App\Controllers\CheckoutController@show', ['roles:customer']);
$router->post('/checkout', 'App\Controllers\CheckoutController@submit', ['roles:customer']);

// Customer orders (Module 5)
$router->get('/orders', 'App\Controllers\OrderController@index', ['roles:customer']);
$router->get('/orders/{id}', 'App\Controllers\OrderController@show', ['roles:customer']);
$router->get('/orders/{id}/status', 'App\Controllers\OrderController@status', ['roles:customer']);
$router->post('/orders/{id}/cancel', 'App\Controllers\OrderController@cancel', ['roles:customer']);
$router->post('/orders/{id}/archive', 'App\Controllers\OrderController@archive', ['roles:customer']);
$router->post('/orders/{id}/reorder', 'App\Controllers\OrderController@reorder', ['roles:customer']);
$router->post('/orders/{id}/review', 'App\Controllers\OrderController@review', ['roles:customer']);

// Restaurant order management (Module 5) — poll BEFORE {id}
$router->get('/owner/orders', 'App\Controllers\Owner\OrderController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/orders/poll', 'App\Controllers\Owner\OrderController@poll', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/orders/{id}', 'App\Controllers\Owner\OrderController@show', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/orders/{id}/transition', 'App\Controllers\Owner\OrderController@transition', ['roles:restaurant_owner,restaurant_staff']);

// Customer payment (Module 6) — simulated gateway
$router->post('/orders/{id}/pay', 'App\Controllers\OrderController@pay', ['roles:customer']);

// Restaurant payment management (Module 6)
$router->get('/owner/payments', 'App\Controllers\Owner\PaymentController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/owner/payments/{id}', 'App\Controllers\Owner\PaymentController@show', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/payments/{id}/void', 'App\Controllers\Owner\PaymentController@void', ['roles:restaurant_owner']);
$router->post('/owner/payments/{id}/delete', 'App\Controllers\Owner\PaymentController@delete', ['roles:restaurant_owner']);

// Restaurant fulfilment management (Module 6)
$router->get('/owner/fulfilments', 'App\Controllers\Owner\FulfilmentController@index', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/fulfilments/{id}/transition', 'App\Controllers\Owner\FulfilmentController@transition', ['roles:restaurant_owner,restaurant_staff']);
$router->post('/owner/fulfilments/{id}/delete', 'App\Controllers\Owner\FulfilmentController@delete', ['roles:restaurant_owner']);

// Role dashboards
$router->get('/dashboard', 'App\Controllers\DashboardController@index', ['auth']);
$router->get('/admin', 'App\Controllers\DashboardController@admin', ['roles:super_admin']);
$router->get('/owner', 'App\Controllers\DashboardController@owner', ['roles:restaurant_owner,restaurant_staff']);
$router->get('/customer', 'App\Controllers\DashboardController@customer', ['roles:customer']);
