
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SupplierController;


Route::controller(SupplierController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/supplier','showAll');
   Route::post('/create/supplier', 'create');
   Route::get('/edit/supplier/{id}','edit');
   Route::post('/update/supplier/{id}', 'update');
   Route::patch('notActive/supplier/{id}', 'notActive');
    Route::patch('active/supplier/{id}', 'active');
   });
