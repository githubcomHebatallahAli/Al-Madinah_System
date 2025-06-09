
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GroupController;


Route::controller(GroupController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/group','showAllWithoutPaginate');
   Route::get('/showAll/group/withPaginate','showAllWithPaginate');
   Route::post('/create/group', 'create');
   Route::get('/edit/group/{id}','edit');
   Route::post('/update/group/{id}', 'update');
   Route::patch('notActive/group/{id}', 'notActive');
    Route::patch('active/group/{id}', 'active');
   });
