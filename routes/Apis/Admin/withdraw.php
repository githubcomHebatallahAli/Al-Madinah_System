
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WithdrawController;

Route::controller(WithdrawController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/withdraw','showAllWithoutPaginate');
   Route::get('/showAll/withdraw/withPaginate','showAllWithPaginate');
   Route::post('/create/withdraw', 'create');
   Route::get('/edit/withdraw/{id}','edit');
   Route::post('/update/withdraw/{id}', 'update');
   });
