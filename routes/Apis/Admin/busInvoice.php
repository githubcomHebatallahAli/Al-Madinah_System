
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BusInvoiceController;


Route::controller(BusInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/busInvoice','showAllWithoutPaginate');
   Route::get('/showAll/busInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/busInvoice', 'create');
    Route::put('/updatePaid/busInvoice/{id}','updatePaidAmount');
   Route::get('/edit/busInvoice/{id}','edit');
   Route::post('/update/busInvoice/{id}', 'update');

   Route::post('/update/incomplete/pilgrims/busInvoice/{busInvoice}', 'updateIncompletePilgrims');
   Route::patch( 'pending/busInvoice/{id}', 'pending');
    Route::patch('approved/busInvoice/{id}', 'approved');
    Route::put('rejected/busInvoice/{id}', 'rejected');
    Route::put('completed/busInvoice/{id}', 'completed');
    Route::put('absence/busInvoice/{id}', 'absence');



   });
