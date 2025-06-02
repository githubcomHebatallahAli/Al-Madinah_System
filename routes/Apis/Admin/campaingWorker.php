
<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CampaignWorkerController;

Route::controller(CampaignWorkerController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::get('/add/Delegets/ToCampaign','addDelegatesToCampaign');
   Route::post('/remove/Delegates/FromCampaign', 'removeDelegatesFromCampaign');
   Route::get('/showAll/Campaign/Delegates','getCampaignDelegates');

   });
