
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
   Route::post('/update/hotelInvoice/{hotelInvoice}', 'update');


   Route::patch( 'pending/hotelInvoice/{id}', 'pending');
    Route::patch('approved/hotelInvoice/{id}', 'approved');
    Route::put('rejected/hotelInvoice/{id}', 'rejected');
    Route::put('completed/hotelInvoice/{id}', 'completed');
    Route::put('absence/hotelInvoice/{id}', 'absence');
    // Route::patch('pending/payment/hotelInvoice/{id}', 'pendingPayment');
    // Route::put('paid/hotelInvoice/{id}', 'paid');
    // Route::put('refunded/hotelInvoice/{id}', 'refunded');


   });
