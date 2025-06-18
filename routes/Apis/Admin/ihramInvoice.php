
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\IhramInvoiceController;


Route::controller(IhramInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/ihramInvoice','showAllWithoutPaginate');
   Route::get('/showAll/ihramInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/ihramInvoice', 'create');
    Route::put('/updatePaid/ihramInvoice/{id}','updatePaidAmount');
   Route::get('/edit/ihramInvoice/{id}','edit');
   Route::post('/update/ihramInvoice/{ihramInvoice}', 'update');

   Route::post('/update/incomplete/pilgrims/ihramInvoice/{ihramInvoice}', 'updateIncompletePilgrims');
   Route::patch( 'pending/ihramInvoice/{id}', 'pending');
    Route::patch('approved/ihramInvoice/{id}', 'approved');
    Route::put('rejected/ihramInvoice/{id}', 'rejected');
    Route::patch('completed/ihramInvoice/{id}', 'completed');
    Route::put('absence/ihramInvoice/{id}', 'absence');
    Route::patch('pending/payment/ihramInvoice/{id}', 'pendingPayment');
    Route::put('paid/ihramInvoice/{id}', 'paid');
    Route::put('refunded/ihramInvoice/{id}', 'refunded');


   });
