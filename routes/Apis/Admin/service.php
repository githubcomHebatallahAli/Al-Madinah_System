
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ServiceController;


Route::controller(ServiceController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/service','showAllWithoutPaginate');
   Route::get('/showAll/service/withPaginate','showAllWithPaginate');
   Route::post('/create/service', 'create');
   Route::get('/edit/service/{id}','edit');
   Route::post('/update/service/{id}', 'update');
   Route::patch('notActive/service/{id}', 'notActive');
    Route::patch('active/service/{id}', 'active');
   });
