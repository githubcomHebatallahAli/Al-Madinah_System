
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WorkerController;


Route::controller(WorkerController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/workerLogin','showAllWorkerLoginWeb');
   Route::get('/showAll/workerLogin/withoutPaginate','showAllWorkerLoginWithoutPaginate');
   Route::get('/showAll/worker','showAllWeb');
   Route::get('/showAll/worker/withPaginate','showAllWithPaginate');

   Route::post('/create/worker', 'create');
   Route::get('/edit/worker/{id}','edit');
   Route::post('/update/worker/{id}', 'update');
   Route::patch('notActive/worker/{id}', 'notActive');
   Route::patch('active/worker/{id}', 'active');
   Route::patch('notActive/workerLogin/{id}', 'notActiveWorkerLogin');
   Route::patch('active/workerLogin/{id}', 'activeWorkerLogin');
   Route::patch('notOk/worker/{id}', 'notOk');
    Route::patch('ok/worker/{id}', 'ok');
   });

Route::controller(WorkerController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/workerLogin/flutter','showAllWorkerLogin');
   Route::get('/showAll/worker/flutter','showAll');
   });
