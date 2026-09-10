<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('api.route_version'))->group(function (): void {
    require config('api.features_routes').'/auth.php';
    require config('api.features_routes').'/admin.php';
});
