
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoreController;


Route::controller(StoreController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
Route::post('/create/store', 'create');
Route::post('/update/store/{id}', 'update');
Route::patch('notActive/store/{id}', 'notActive');
Route::patch('active/store/{id}', 'active');
Route::get('/showAll/title/withoutPaginate','showAllWithoutPaginate');
   });
Route::controller(StoreController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/store','showAll');
   Route::get('/edit/store/{id}','edit');
     Route::get('/showAll/title/withPaginate','showAllWithPaginate');

   });
