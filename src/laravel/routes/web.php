<?php

use Illuminate\Support\Facades\Route;

Route::any('/{path?}', function () {
    ob_start();
    require base_path('../website/index.php');

    return response(ob_get_clean());
})->where('path', '.*');
