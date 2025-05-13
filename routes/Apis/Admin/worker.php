
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WorkerController;


Route::controller(WorkerController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/worker','showAll');
   Route::post('/create/worker', 'create');
   Route::get('/edit/worker/{id}','edit');
   Route::post('/update/worker/{id}', 'update');
   Route::patch('notActive/worker/{id}', 'notActive');
    Route::patch('active/worker/{id}', 'active');
   });
