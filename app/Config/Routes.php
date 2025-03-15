<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Lead Management Routes
$routes->group('leads', function($routes) {
    $routes->get('/', 'LeadController::index');
    $routes->get('create', 'LeadController::create');
    $routes->post('store', 'LeadController::store');
    $routes->get('edit/(:num)', 'LeadController::edit/$1');
    $routes->post('update/(:num)', 'LeadController::update/$1');
    $routes->delete('delete/(:num)', 'LeadController::delete/$1');
    $routes->post('import', 'LeadController::import');
    $routes->get('export', 'LeadController::export');
    $routes->get('download-template', 'LeadController::downloadTemplate');
    $routes->post('ajax-list', 'LeadController::ajaxList');
    $routes->post('leads/delete/(:num)', 'LeadController::delete/$1');
});
