<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;


Route::controller(RoleController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

Route::get('/showAll/role','showAll');
Route::post('/create/role', 'create');
Route::get('/edit/role/{id}','edit');
Route::post('/update/role/{id}', 'update');
Route::patch('notActive/role/{id}', 'notActive');
Route::patch('active/role/{id}', 'active');
   });
