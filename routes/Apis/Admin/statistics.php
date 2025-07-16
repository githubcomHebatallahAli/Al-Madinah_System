<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\StatisticsController;


Route::controller(StatisticsController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
        Route::get('/showAll/statistics', 'showStatistics');
    });
