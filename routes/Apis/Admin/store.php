
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoreController;


Route::controller(StoreController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
Route::post('/create/branch', 'create');
Route::post('/update/branch/{id}', 'update');
Route::patch('notActive/branch/{id}', 'notActive');
Route::patch('active/branch/{id}', 'active');
   });
Route::controller(StoreController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/store','showAll');
   Route::get('/edit/store/{id}','edit');
   });
