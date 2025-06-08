
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BusController;


Route::controller(BusController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/bus','showAll');
   Route::post('/create/bus', 'create');
   Route::get('/edit/bus/{id}','edit');
   Route::post('/update/bus/{id}', 'update');
   Route::post('/update/seatMap/bus/{id}', 'updateSeatMap');
   Route::patch('notActive/bus/{id}', 'notActive');
    Route::patch('active/bus/{id}', 'active');
   });
