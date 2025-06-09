
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\IhramSupplyController;


Route::controller(IhramSupplyController::class)->prefix('/adminOrBranchMangerOrStorekeeper')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/ihramSupply','showAllWithoutPaginate');
   Route::get('/showAll/ihramSupply/withPaginate','showAllWithPaginate');
   Route::get('/edit/ihramSupply/{id}','edit');


   });

Route::controller(IhramSupplyController::class)->prefix('/Storekeeper')->middleware('adminOrWorker')->group(
    function () {
   Route::post('/create/ihramSupply', 'create');
   Route::post('/update/ihramSupply/{id}', 'update');
   Route::patch('notActive/ihramSupply/{id}', 'notActive');
Route::patch('active/ihramSupply/{id}', 'active');
   });
