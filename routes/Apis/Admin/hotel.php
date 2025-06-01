
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\HotelController;


Route::controller(HotelController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/hotel','showAll');
   Route::post('/create/hotel', 'create');
   Route::get('/edit/hotel/{id}','edit');
   Route::post('/update/hotel/{id}', 'update');
   Route::patch('notActive/hotel/{id}', 'notActive');
    Route::patch('active/hotel/{id}', 'active');
   });
