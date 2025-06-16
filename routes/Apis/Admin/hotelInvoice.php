
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\HotelInvoiceController;


Route::controller(HotelInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/hotelInvoice','showAllWithoutPaginate');
   Route::get('/showAll/hotelInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/hotelInvoice', 'create');
    Route::put('/updatePaid/hotelInvoice/{id}','updatePaidAmount');
   Route::get('/edit/hotelInvoice/{id}','edit');
   Route::post('/update/hotelInvoice/{id}', 'update');

   Route::post('/update/incomplete/pilgrims/hotelInvoice/{hotelInvoice}', 'updateIncompletePilgrims');
   Route::patch( 'pending/hotelInvoice/{id}', 'pending');
    Route::patch('approved/hotelInvoice/{id}', 'approved');
    Route::patch('rejected/hotelInvoice/{id}', 'rejected');
    Route::patch('completed/hotelInvoice/{id}', 'completed');
    Route::patch('absence/hotelInvoice/{id}', 'absence');
    Route::patch('pending/payment/hotelInvoice/{id}', 'pendingPayment');
    Route::patch('paid/hotelInvoice/{id}', 'paid');
    Route::patch('refunded/hotelInvoice/{id}', 'refunded');


   });
