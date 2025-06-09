
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FlightController;


Route::controller(FlightController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/flight','showAllWithoutPaginate');
   Route::get('/showAll/flight/withPaginate','showAllWithPaginate');
   Route::post('/create/flight', 'create');
   Route::get('/edit/flight/{id}','edit');
   Route::post('/update/flight/{id}', 'update');
   Route::patch('notActive/flight/{id}', 'notActive');
    Route::patch('active/flight/{id}', 'active');
   });
