
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ShipmentController;


Route::controller(ShipmentController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/shipment','showAllWithoutPaginate');
   Route::get('/showAll/shipment/withPaginate','showAllWithPaginate');
   Route::post('/create/shipment', 'create');
   Route::get('/edit/shipment/{id}','edit');
   Route::post('/update/shipment/{id}', 'update');
   Route::patch('notActive/shipment/{id}', 'notActive');
    Route::patch('active/shipment/{id}', 'active');
   });
