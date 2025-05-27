
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OfficeController;


Route::controller(OfficeController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {
   Route::get('/showAll/office','showAll');
   Route::get('/edit/office/{id}','edit');

   });

   Route::controller(OfficeController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
    Route::post('/create/office', 'create');
    Route::post('/update/office/{id}', 'update');
    Route::patch('notActive/office/{id}', 'notActive');
    Route::patch('active/office/{id}', 'active');
   });
