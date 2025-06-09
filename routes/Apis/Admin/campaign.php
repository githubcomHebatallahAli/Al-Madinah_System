
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CampaignController;


Route::controller(CampaignController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/showAll/campaign','showAllWithoutPaginate');
   Route::get('/showAll/campaign/withPaginate','showAllWithPaginate');
   Route::post('/create/campaign', 'create');
   Route::get('/edit/campaign/{id}','edit');
   Route::post('/update/campaign/{id}', 'update');
   Route::patch('notActive/campaign/{id}', 'notActive');
    Route::patch('active/campaign/{id}', 'active');
   });
