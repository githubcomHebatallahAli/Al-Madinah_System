
<?php



use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CampaignWorkerController;

Route::controller(CampaignWorkerController::class)->prefix('/adminOrBranchManger')->middleware('adminOrWorker')->group(
    function () {

   Route::post('/add/Delegets/ToCampaign/{campaignId}','addDelegatesToCampaign');
   Route::delete('/remove/Delegates/FromCampaign/{campaignId}', 'removeDelegatesFromCampaign');
   Route::get('/show/Campaign/{campaignId}/Delegates','getCampaignDelegates');

   });
