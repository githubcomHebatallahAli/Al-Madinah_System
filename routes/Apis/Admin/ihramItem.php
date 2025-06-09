
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\IhramItemController;


Route::controller(IhramItemController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/ihramItem','showAllWitoutPaginate');
   Route::get('/showAll/ihramItem/withPaginate','showAllWithPaginate');
   Route::get('/edit/ihramItem/{id}','edit');
    Route::post('/create/ihramItem', 'create');
   Route::post('/update/ihramItem/{id}', 'update');
   Route::patch('notActive/ihramItem/{id}', 'notActive');
    Route::patch('active/ihramItem/{id}', 'active');
   });


