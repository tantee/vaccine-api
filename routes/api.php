<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use TaNteE\LaravelGenericApi\LaravelGenericApi;
use TaNteE\LaravelGenericApi\Http\Controllers\GenericAPIController;
use TaNteE\LaravelModelApi\LaravelModelApi;
use TaNteE\LaravelModelApi\Http\Controllers\ModelAPIController;
use TaNteE\LaravelModelApi\Http\Controllers\Asset\AssetController;

use App\Http\Controllers\Login\LoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/user/login',[LoginController::class,'Login']);
Route::middleware('auth:sanctum')->prefix('user')->group(function() {
  Route::post('/', [LoginController::class,'User']);
  Route::post('/logout',[LoginController::class,'Logout']);
  Route::post('/logoutall',[LoginController::class,'LogoutAll']);
  Route::post('/verify',[LoginController::class,'verifyPassword']);
});

Route::get('Master/MasterController/getMasterItems',function (Request $request) {
  return GenericAPIController::route($request,'Master','MasterController','getMasterItems');
});
Route::post('Client/ClientController/{methodName}',function (Request $request,$methodName) {
  return GenericAPIController::route($request,'Client','ClientController',$methodName);
});
Route::post('models/Client/Clients/first',function (Request $request) {
  return ModelAPIController::methodRouting($request,'Client','Clients','first');
});

Route::post('public/models/Document/{modelName}/{method}',function (Request $request,$modelName,$method) {
  return ModelAPIController::methodRouting($request,'Document',$modelName,$method);
});

Route::get('/assets/{id}',[AssetController::class,'getAsset']);

LaravelModelApi::routes(null,'auth:sanctum');
LaravelGenericApi::routes(null,'auth:sanctum');