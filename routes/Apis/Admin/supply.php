
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SupplyController;


Route::controller(SupplyController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/supply','showAll');
   Route::post('/create/supply', 'create');
   Route::get('/edit/supply/{id}','edit');
   Route::post('/update/supply/{id}', 'update');
   Route::patch('notActive/supply/{id}', 'notActive');
    Route::patch('active/supply/{id}', 'active');
   });
