
<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CityController;


Route::controller(CityController::class)->prefix('/admin')->middleware('admin')->group(
    function () {

   Route::get('/showAll/city','showAll');
   Route::post('/create/city', 'create');
   Route::get('/edit/city/{id}','edit');
   Route::post('/update/city/{id}', 'update');
   Route::patch('notActive/city/{id}', 'notActive');
Route::patch('active/city/{id}', 'active');
   });
