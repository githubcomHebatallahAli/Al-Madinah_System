
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\FlightInvoiceController;


Route::controller(FlightInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/flightInvoice','showAllWithoutPaginate');
   Route::get('/showAll/flightInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/flightInvoice', 'create');
    Route::put('/updatePaid/flightInvoice/{id}','updatePaidAmount');
   Route::get('/edit/flightInvoice/{id}','edit');
   Route::post('/update/flightInvoice/{FlightInvoice}', 'update');


   Route::patch( 'pending/flightInvoice/{id}', 'pending');
    Route::patch('approved/flightInvoice/{id}', 'approved');
    Route::put('rejected/flightInvoice/{id}', 'rejected');
    Route::put('completed/flightInvoice/{id}', 'completed');
    Route::put('absence/flightInvoice/{id}', 'absence');

   });
