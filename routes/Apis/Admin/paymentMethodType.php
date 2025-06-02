
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentMethodTypeController;


Route::controller(PaymentMethodTypeController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/paymentMethodType','showAll');
   Route::post('/create/paymentMethodType', 'create');
   Route::get('/edit/paymentMethodType/{id}','edit');
   Route::post('/update/paymentMethodType/{id}', 'update');
   Route::patch('notActive/paymentMethodType/{id}', 'notActive');
    Route::patch('active/paymentMethodType/{id}', 'active');
   });
