
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CompanyController;


Route::controller(CompanyController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/company','showAllWithoutPaginate');
   Route::get('/showAll/company/withPaginate','showAllWithPaginate');
   Route::post('/create/company', 'create');
   Route::get('/edit/company/{id}','edit');
   Route::post('/update/company/{id}', 'update');
   Route::patch('notActive/company/{id}', 'notActive');
    Route::patch('active/company/{id}', 'active');
   });
