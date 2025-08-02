<?php

use App\Http\Controllers\Auth\CloseController;

use Illuminate\Support\Facades\Route;



Route::controller(CloseController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
        Route::get('/app/status', 'getStatus');
        Route::post('/close/toggle', 'toggleStatus');

});