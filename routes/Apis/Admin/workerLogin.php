
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WorkerLoginController;



Route::controller(WorkerLoginController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::patch('notActive/workerLogin/{id}', 'notActive');
   Route::patch('active/workerLogin/{id}', 'active');
 });
