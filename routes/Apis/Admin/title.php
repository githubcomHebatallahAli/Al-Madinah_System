
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TitleController;


Route::controller(TitleController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/title','showAll');
   Route::post('/create/title', 'create');
   Route::get('/edit/title/{id}','edit');
   Route::post('/update/title/{id}', 'update');
   Route::patch('notActive/title/{id}', 'notActive');
    Route::patch('active/title/{id}', 'active');
   });
