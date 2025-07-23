
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MainInvoiceController;


Route::controller(MainInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/mainInvoice','showAllWithoutPaginate');
   Route::get('/showAll/mainInvoice/withPaginate','showAllWithPaginate');
   Route::post('/create/mainInvoice', 'create');
    Route::put('/updatePaid/mainInvoice/{id}','updatePaidAmount');
   Route::get('/edit/mainInvoice/{id}','edit');
   Route::post('/update/mainInvoice/{id}', 'update');


   Route::patch( 'pending/mainInvoice/{id}', 'pending');
    Route::patch('approved/mainInvoice/{id}', 'approved');
    Route::put('rejected/mainInvoice/{id}', 'rejected');
    Route::put('completed/mainInvoice/{id}', 'completed');
    Route::put('absence/mainInvoice/{id}', 'absence');

    Route::get('/test-whatsapp', 'sendTestMessage');
   





   });
