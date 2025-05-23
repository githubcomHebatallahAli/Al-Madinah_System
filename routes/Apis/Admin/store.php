
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoreController;



Route::controller(StoreController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/store','showAll');
   Route::post('/create/store', 'create');
   Route::get('/edit/store/{id}','edit');
   Route::post('/update/store/{id}', 'update');
   Route::patch('notActive/store/{id}', 'notActive');
    Route::patch('active/store/{id}', 'active');
   });
