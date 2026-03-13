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

$data = json_decode($response->getContent(), true);
foreach ($data as $cat) {
    if ($cat['name'] === 'Personal Development') {
        echo "Category: " . $cat['name'] . "\n";
        foreach ($cat['subcategories'] as $sub) {
            echo "  Sub: " . $sub['name'] . " (Packages: " . count($sub['businesses'][0]['scales']) . ")\n";
            foreach ($sub['businesses'][0]['scales'] as $scale) {
                echo "    - Scale: " . $scale['name'] . " (Price: " . $scale['custom_price'] . ")\n";
            }
        }
    }
}
