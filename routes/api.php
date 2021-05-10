<?php

use Illuminate\Http\Request;

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
Route::get('version','ApiController@version');

Route::post('/user/login',function(Request $request){
});

Route::middleware('auth:api')->prefix('user')->group(function() {
  Route::get('/', function (Request $request) { return $request->user(); });
  Route::get('/logout', function (Request $request) {
    $request->user()->token()->revoke();
    return [];
  });
  Route::middleware('throttle:10,1')->post('/verify','Auth\UserController@verifyPassword');
});

Route::get('Master/MasterController/getMasterItems',function (Request $request) {
  return \App\Http\Controllers\GenericAPIController::route($request,'Master','MasterController','getMasterItems');
});
Route::post('Client/ClientController/{methodName}',function (Request $request,$methodName) {
  return \App\Http\Controllers\GenericAPIController::route($request,'Client','ClientController',$methodName);
});
Route::post('models/Client/Clients/first',function (Request $request) {
  return \App\Http\Controllers\ModelAPIController::methodRouting($request,'Client','Clients','first');
});

Route::post('public/models/Document/{modelName}/{method}',function (Request $request,$modelName,$method) {
  return \App\Http\Controllers\ModelAPIController::methodRouting($request,'Document',$modelName,$method);
});

Route::middleware('auth:api')->group(function() {
  Route::prefix('models')->group(function () {
    Route::get('{modelNamespace}/{modelName}','ModelAPIController@readRouting');
    Route::post('{modelNamespace}/{modelName}/{method}','ModelAPIController@methodRouting');
  });
  
  Route::get('{methodNamespace}/{methodClass}/{methodName}','GenericAPIController@route');
  Route::post('{methodNamespace}/{methodClass}/{methodName}','GenericAPIController@route');
  Route::get('direct/{methodNamespace}/{methodClass}/{methodName}','GenericAPIController@routeDirect');
  Route::post('direct/{methodNamespace}/{methodClass}/{methodName}','GenericAPIController@routeDirect');
});

Route::get('/assets/{id}','Asset\AssetController@getAsset');

Route::get('IDCard/read',function (Request $request) {
  $returnJson = '{"success":true,"data":{"AtrString":"3B781800005448204E49442039","FormatVersion":null,"NationalID":"1103701110317","ThaiTitleName":"นาย","ThaiFirstName":"มงคล","ThaiMiddleName":"","ThaiLastName":"กิจรุ่งวิริยะ","EnglishTitleName":"Mr.","EnglishFirstName":"Mongkol","EnglishMiddleName":"","EnglishLastName":"Kijroongviriya","Birthdate":"2536-06-16","Sex":"1","Address":"16/513","Moo":"หมู่ที่ 8","Trok":"","Soi":"","Thanon":"","Tumbol":"ตำบลสวนใหญ่","Amphur":"อำเภอเมืองนนทบุรี","Province":"จังหวัดนนทบุรี","IssuePlace":"ท้องถิ่นเขตพระนคร/กรุงเทพมหานคร","IssueDate":"2558-08-07","ExpireDate":"2567-06-15","Base64Photo":"/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAGRFS1hLP2RYUVhxamR3lvqjloqKlv/b57X6////////////////////////////////////////////////////2wBDAWpxcZaDlv+jo///////////////////////////////////////////////////////////////////////////wAARCAFjASkDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwC1RRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUEgdaACimGVexzUZuVHagCeioPtI9KPtK0AT0VEJwelPDg0AOooBzRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRnFJketAC0Um4etG4etAC0Um4etG4etAC0Um5fWjevrQAtFN3r60b19aAHU13VBljTZJlRc5yapPIznLGgCd7o/wioGkZjyaZRQAuTijNJRQAuaTNFFABmpo5iOG5qGigC+rjGRUgORms9ZCvAqeG4AyGoAtUVEJ0NL5y0ASUUzzV96PNHvQA+imeaPQ0eaPQ0APopnmj0NHmD0NAD6KZ5v8AsmjzP9k0APop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8GpazQcGp47gjhuRSGW6Karq3Q06gAooooAZSU13VByarSTM3A4FAEskwXgcmqzOWOSaSgDNABShSxwBmp4rZm5bgVaSNUHyigCvFa93/KrIUKMAYFLUbzKvuaAJKaHVjgGqrzM/sKYCQcinYC/TGj9OKjjnB4apwQelICAgjrSVYppQGkBDThTjGe1JtIoGJRS0UAFFFFACilpKUCgAopdtOxQA3GaUAClpruFHvTEKSAMmlBBGRVV3LGhXK9DTsK5aprxq4wwpEkD+xp9IZSlgZORyKiNaVV5rcHlOvpQBUpaCCDg0lMBysVPBqzHcA8NVSlpAaIII4paopKyHg1L9qPpQBVYknmkoooAKtWqqRnHNFFAFqiiigCGdiOAarUUU0IKKKKYhKmiY5HNFFAy0OlFFFSMKKKKADFJgelFFABgUYFFFAC4ooooAKKKKAGSEheKrMcmiimgEoHSiimSKKtIcqM0UUmUOooopAQ3KgpnHNUqKKACiiimAUUUUAf/ZICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg","RequestNo":"10014186732/08071339","IssuerCode":"3100201191818","CardType":"01","PhotoRefNo":"10010208071339"},"errorTexts":[]}';
  return response($returnJson)->header('content-type','application/json');
});

// try {
//   $apis = \App\Models\Api\Apis::all();
//   foreach($apis as $api) {
//     Route::match([$api->apiMethod],'/wrapper/'.ltrim($api->apiRoute,'/'),function(Request $request) use ($api) {
//       $args = func_get_args();
//       array_shift($args);
//       $apiMethod = (!empty($api->sourceApiMethod)) ? $api->sourceApiMethod : $api->ApiMethod;
//       return \App\Http\Controllers\ApiController::RemoteRESTApiRequest($request,$api->name,$apiMethod,$api->sourceApiUrl,$api->ETLCode,$api->ETLCodeError,$api->isFlatten,$api->isMaskError,$args);
//     });
//   };
// } catch (\Exception $e) {
// }