<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use App\Models\User;

$id = null;
if (method_exists(Setting::class, 'get')) {
    $id = Setting::get('super_admin_id');
}

if ($id) {
    $u = User::find($id);
    if ($u) {
        echo $u->email . "|ID:" . $u->id . "|ROLE:" . ($u->role ?? 'n/a') . PHP_EOL;
        exit(0);
    }
    echo "user-not-found\n";
    exit(0);
}

$u = User::where('email', 'admin@conza.local')->first();
if ($u) {
    echo $u->email . "|ID:" . $u->id . "|ROLE:" . ($u->role ?? 'n/a') . PHP_EOL;
} else {
    echo "not-found\n";
}
