<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\WorkerAuthController;


Route::controller(AdminAuthController::class)->prefix('/admin')->group(
    function () {
Route::post('/login', 'login');
Route::post('/register',  'register');
  Route::middleware(['auth:admin'])->group(function () {
        Route::post('/logout', 'logout');
        Route::post('/refresh', 'refresh');
        Route::get('/user-profile', 'userProfile');
    });

});

Route::controller(WorkerAuthController::class)->prefix('/worker')->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
    Route::middleware(['auth:worker'])->group(function () {
        Route::post('/logout', 'logout');
        Route::post('/refresh', 'refresh');
        Route::get('/worker-profile', 'userProfile');
    });
});



