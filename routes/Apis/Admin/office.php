
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OfficeController;


Route::controller(OfficeController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/edit/office/{id}','edit');
   Route::get('/showAll/office','showAllWithoutPaginate');
    Route::get('/showAll/office/withPaginate','showAllWithPaginate');



   });

   Route::controller(OfficeController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
    Route::post('/create/office', 'create');
    Route::post('/update/office/{id}', 'update');
    Route::patch('notActive/office/{id}', 'notActive');
    Route::patch('active/office/{id}', 'active');

   });
