
<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BusInvoiceController;


Route::controller(BusInvoiceController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/busInvoice','showAll');
   Route::post('/create/busInvoice', 'create');
    Route::put('/updatePaid/busInvoice/{id}','updatePaidAmount');
   Route::get('/edit/busInvoice/{id}','edit');
   Route::post('/update/busInvoice/{id}', 'update');
   Route::patch('notActive/busInvoice/{id}', 'notActive');
    Route::patch('active/busInvoice/{id}', 'active');
   });
