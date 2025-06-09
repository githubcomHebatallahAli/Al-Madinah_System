
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentMethodController;


Route::controller(PaymentMethodController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/paymentMethod','showAllWithoutPaginate');
   Route::get('/showAll/paymentMethod/withPaginate','showAllWithPaginate');
   Route::post('/create/paymentMethod', 'create');
   Route::get('/edit/paymentMethod/{id}','edit');
   Route::post('/update/paymentMethod/{id}', 'update');
   Route::patch('notActive/paymentMethod/{id}', 'notActive');
    Route::patch('active/paymentMethod/{id}', 'active');
   });
