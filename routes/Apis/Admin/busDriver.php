
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BusDriverController;


Route::controller(BusDriverController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/busDriver','showAll');
   Route::post('/create/busDriver', 'create');
   Route::get('/edit/busDriver/{id}','edit');
   Route::post('/update/busDriver/{id}', 'update');
   Route::patch('notActive/busDriver/{id}', 'notActive');
    Route::patch('active/busDriver/{id}', 'active');
   });
