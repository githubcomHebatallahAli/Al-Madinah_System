
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BusTripController;


Route::controller(BusTripController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/busTrip','showAllWithoutPaginate');
   Route::get('/showAll/busTrip/withPaginate','showAllWithPaginate');
   Route::post('/create/busTrip', 'create');
   Route::get('/edit/busTrip/{id}','edit');
   Route::post('/update/busTrip/{id}', 'update');
    Route::patch('notActive/busTrip/{id}', 'notActive');
    Route::patch('active/busTrip/{id}', 'active');
    Route::put('update/SeatStatus/busTrip/{id}', 'updateSeatStatus');

   });
