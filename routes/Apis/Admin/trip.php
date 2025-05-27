
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TripController;


Route::controller(TripController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/trip','showAll');
   Route::post('/create/trip', 'create');
   Route::get('/edit/trip/{id}','edit');
   Route::post('/update/trip/{id}', 'update');
   Route::patch('notActive/trip/{id}', 'notActive');
    Route::patch('active/trip/{id}', 'active');
   });
