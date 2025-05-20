
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BranchController;


Route::controller(BranchController::class)->prefix('/admin')->middleware('admin')->group(
    function () {
   Route::post('/create/branch', 'create');
   Route::post('/update/branch/{id}', 'update');
   Route::patch('notActive/branch/{id}', 'notActive');
Route::patch('active/branch/{id}', 'active');
   });

   Route::controller(BranchController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {
         Route::get('/edit/branch/{id}','edit');
        Route::get('/showAll/branch','showAll');

           });

