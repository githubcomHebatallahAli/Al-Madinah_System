
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ShipmentInvoiceController;


Route::controller(ShipmentInvoiceController::class)->prefix('/supplier')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/shipmentInvoice','showAllWithoutPaginate');
   Route::get('/showAll/shipmentInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/shipmentInvoice', 'create');
    Route::put('/updatePaid/shipmentInvoice/{id}','updatePaidAmount');
   Route::get('/edit/shipmentInvoice/{id}','edit');
   Route::post('/update/shipmentInvoice/{id}', 'update');
   Route::patch('notActive/shipmentInvoice/{id}', 'notActive');
    Route::patch('active/shipmentInvoice/{id}', 'active');
   });
