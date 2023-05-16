<?php

use Grimarina\CityBike\http\{Request, ErrorResponse};
use Grimarina\CityBike\Exceptions\HttpException;
use Grimarina\CityBike\Actions\Stations\{FindAllStations, FindStationById};
use Grimarina\CityBike\Actions\Trips\{FindAllTrips};
use Grimarina\CityBike\Repositories\{StationsRepository, TripsRepository};

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

$request = new Request($_GET, $_SERVER, file_get_contents('php://input'),);

try {
    $path = $request->path();
} catch (HttpException) {
    (new ErrorResponse)->send();
    return;
}

try {
    $method = $request->method();
} catch (HttpException) {
    (new ErrorResponse)->send();
    return;
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
} catch (PDOException $e) {
    throw new RuntimeException('Failed to connect to database: ' . $e->getMessage());
}

// Define the available routes and corresponding actions
$routes = [
    'GET' => [
        '/stations/show' => new FindAllStations(new StationsRepository($pdo)),
        '/station/show' => new FindStationById(new StationsRepository($pdo)),
        '/trips/show' => new FindAllTrips(new TripsRepository($pdo)),
    ]
];

// Check if the requested method exists
if (!array_key_exists($method, $routes)) {
    (new ErrorResponse('Not found'))->send();
    return;
}

// Check if the requested path is valid
if (!array_key_exists($path, $routes[$method])) {
    (new ErrorResponse('Not found'))->send();
    return;
}

// Get the action associated with the requested route
$action = $routes[$method][$path];

try {
    // Handle the request using the action
    $response = $action->handle($request);
} catch (Exception $e) {
    (new ErrorResponse($e->getMessage()))->send();
}

$response->send();
