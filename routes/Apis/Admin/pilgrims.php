
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PilgrimsController;


Route::controller(PilgrimsController::class)->prefix('/adminOrBranchMangerOrDelegate')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/pilgrims','showAllWithoutPaginate');
   Route::get('/showAll/pilgrims/withPaginate','showAllWithPaginate');
   Route::get('/edit/pilgrims/{id}','edit');
   });

Route::controller(PilgrimsController::class)->prefix('/delegate')->middleware('adminOrWorker')->group(
    function () {
   Route::post('/create/pilgrims', 'create');
   Route::post('/update/pilgrims/{id}', 'update');
   Route::patch('notActive/pilgrims/{id}', 'notActive');
Route::patch('active/pilgrims/{id}', 'active');
   });
