<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;

$request = new Request(['intent' => 'personalServices']);
$controller = new ProductController();
$response = $controller->index($request);

header('Content-Type: application/json');
echo $response->getContent();
