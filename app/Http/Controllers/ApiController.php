<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    public static function RemoteApiRequest(Request $request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args) {
      if ($ApiMethod=="GET" || $ApiMethod=="POST" || $ApiMethod=="PUT" || $ApiMethod=="PATCH") {
        return self::RemoteRESTApiRequest($request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args);
      } else {
        return self::RemoteSOAPApiRequest($request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args);
      }
    }

    public static function RemoteRESTApiRequest(Request $request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      for($i=0;$i<count($args);$i++) {
        $ApiUrl = str_replace('{'.($i+1).'}',$args[$i],$ApiUrl);
      }

      $requestData = [
        'headers' => [
          'Accept' => 'application/json',
        ]
      ];

      parse_str(parse_url($ApiUrl, PHP_URL_QUERY), $queryarray);
      $queryarray = array_merge($queryarray,$request->query());

      if (!empty($queryarray)) $requestData['query']=$queryarray;

      if ($request->header('Content-Type')=="application/json") {
        $requestData['json'] = $request->json()->all();
        $requestData['headers']['Content-Type'] = "application/json";
      }
      if ($request->header('Content-Type')=="application/x-www-form-urlencoded") {
        $requestData['form_params'] = array_diff($request->input(),$request->query());
        $requestData['headers']['Content-Type'] = "application/x-www-form-urlencoded";
      }

      $client = new \GuzzleHttp\Client();
      $httpResponseCode = '';
      $httpResponseReason = '';
      if ($ApiMethod != null && $ApiUrl != null) {
        try {

          $res = $client->request($ApiMethod,$ApiUrl,$requestData);
          Log::info('Calling '.$ApiName.' ('.$ApiMethod.' '.$ApiUrl.')',["RequestData"=>$requestData]);

          $httpResponseCode = $res->getStatusCode();
          $httpResponseReason = $res->getReasonPhrase();
          if ($httpResponseCode!==200) {
            $success = false;
            array_push($errorTexts,['errorText'=>$res->getBody(),'errorType'=>2]);

            try {
              if (!empty($ETLCodeError)) eval($ETLCodeError);
            } catch(\Exception $e) {
              log::error("Data transformation logic error (API Error)");
              return response("Data transformation logic error",501);
            }
          } else {
            $ApiData = json_decode((String)$res->getBody(),true);
            if ($ApiData != null) {
              $success = array_pull($ApiData,'success',$success);
              $errorTexts = array_pull($ApiData,'errorTexts',$errorTexts);
            }

            if ($isMaskError) {
              array_walk($errorTexts,function(&$value,$key) {
                if (isset($value['errorType']) && $value['errorType']!=1) {
                  $value['errorText'] = 'Internal Server Error';
                }
              });
            }

            try {
              if (!empty($ETLCode)) eval($ETLCode);
              else $returnModels = $ApiData;
            } catch(\Exception $e) {
              log::error("Data transformation logic error (API Data)",["ETLCode"=>$ETLCode,"APIData"=>$ApiData]);
              return response("Data transformation logic error",501);
            }
          }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          $response = $e->getResponse();
          if ($response) {
            $httpResponseCode = $response->getStatusCode();
            $httpResponseReason = $response->getReasonPhrase();
          } else {
            $httpResponseCode = 500;
            $httpResponseReason = $e->getMessage();
          }

          $success = false;
          array_push($errorTexts,['errorText'=>$e->getMessage(),'errorType'=>2]);

          if ($isMaskError) {
            $httpResponseCode = 500;
            $httpResponseReason = 'Internal Server Error';
            $errorTexts = [
              [
                'errorText' => 'Internal Server Error',
                'errorType' => 2
              ]
            ];
          }

          try {
            if (!empty($ETLCodeError)) eval($ETLCodeError);
          } catch(\Exception $e) {
            log::error("Data transformation logic error (API Error)");
            return response("Data transformation logic error",501);
          }
        }
      } else {
        try {
          if (!empty($ETLCode)) eval($ETLCode);
        } catch(\Exception $e) {
          log::error("Data transformation logic error (API Data)",["ETLCode"=>$ETLCode]);
          return response("Data transformation logic error",501);
        }
      }

      if ($isFlatten) \Illuminate\Http\Resources\Json\Resource::withoutWrapping();

      if ($returnModels instanceof Illuminate\Database\Eloquent\Collection) {
        if (!$isFlatten) return new \App\Http\Resources\ExtendedResourceCollection($returnModels,$success,$errorTexts);
        else return new \Illuminate\Http\Resources\Json\ResourceCollection($returnModels);
      } else {
        if (!is_array($returnModels)) $returnModels = (array)$returnModels;
        if (!$isFlatten) return new \App\Http\Resources\ExtendedResource($returnModels,$success,$errorTexts);
        else return new \Illuminate\Http\Resources\Json\JsonResource($returnModels);
      }
    }

    public static function RemoteRESTApi($ApiName,$CallData,$cache=0) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $CallDataHash = md5(json_encode($CallData));
      $cacheKey = $ApiName.'#'.$CallDataHash;

      if ($cache && Cache::has($cacheKey)) {
        log::info('retrieve data from cache');
        return Cache::get($cacheKey);
      }

      $api = \App\Models\Api\Apis::where('name',$ApiName)->first();
      if ($api == null) {
        $success = false;
        array_push($errorTexts,['errorText'=>"API Not Found",'errorType'=>2]);
      } else {
        $requestData = [
          'headers' => [
            'Accept' => 'application/json',
          ],
        ];
        $ApiUrl = $api->sourceApiUrl;

        if (is_array($CallData)) {
          $keys = array_keys($CallData);
          for($i=0;$i<count($keys);$i++) {
            $ApiUrl = str_replace('{'.($i+1).'}',$CallData[$keys[$i]],$ApiUrl);
          }
        }
        
        $apiMethod = (!empty($api->sourceApiMethod)) ? $api->sourceApiMethod : $api->ApiMethod;

        if ($apiMethod=="GET") {
          parse_str(parse_url($ApiUrl, PHP_URL_QUERY), $queryarray);
          $queryarray = array_merge($queryarray,$CallData);

          if (!empty($queryarray)) $requestData['query']=$queryarray;
        } else {
          $requestData['json'] = is_array($CallData) ? $CallData : [$CallData];
          $requestData['headers']['Content-Type'] = "application/json";
        }

        $client = new \GuzzleHttp\Client();
        $httpResponseCode = '';
        $httpResponseReason = '';

        try {

          $res = $client->request($apiMethod,$ApiUrl,$requestData);

          $httpResponseCode = $res->getStatusCode();
          $httpResponseReason = $res->getReasonPhrase();
          if ($httpResponseCode!==200) {
            $success = false;
            array_push($errorTexts,['errorText'=>$res->getBody(),'errorType'=>2]);

            try {
              if (!empty($api->ETLCodeError)) eval($api->ETLCodeError);
            } catch(\Exception $e) {
              array_push($errorTexts,['errorText'=>"Data transformation logic error (API Error)",'errorType'=>1]);
            }
          } else {
            $ApiData = json_decode((String)$res->getBody(),true);
            if ($ApiData != null) {
              $success = array_pull($ApiData,'success',$success);
              $errorTexts = array_pull($ApiData,'errorTexts',$errorTexts);
            }

            try {
              if (!empty($api->ETLCode)) eval($api->ETLCode);
              else $returnModels = $ApiData;
            } catch(\Exception $e) {
              array_push($errorTexts,['errorText'=>"Data transformation logic error (API Error)",'errorType'=>1]);
            }
          }

          if ($cache) {
            Cache::put($cacheKey,["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels],$cache);
          }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $apiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          $success = false;
          array_push($errorTexts,['errorText'=>$e->getMessage(),'errorType'=>2]);
        }
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function RemoteSOAPApiRequest(Request $request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if ($ApiMethod != null && $ApiUrl != null) {

        $CallData = [];

        if ($request->header('Content-Type')=="application/json") {
          $requestJson = $request->json()->all();
          if (isset($requestJson['data'])) $CallData = $requestJson['data'];
          else $CallData = $requestJson;
        }
        if ($request->header('Content-Type')=="application/x-www-form-urlencoded") {
          $CallData = $request->input();
        }

        $CallData = array_merge($CallData,$args);

        $result = self::RemoteSOAPApiRaw($ApiUrl,$ApiMethod,$CallData,$ETLCode,$ETLCodeError);

        $success = $result['success'];
        $errorTexts = $result['errorTexts'];
        $returnModels = $result['returnModels'];

      } else {
        try {
          if (!empty($ETLCode)) eval($ETLCode);
        } catch(\Exception $e) {
          log::error("Data transformation logic error (API Data)",["ETLCode"=>$ETLCode]);
          return response("Data transformation logic error",501);
        }
      }

      if ($isMaskError) {
        array_walk($errorTexts,function(&$value,$key) {
          if (isset($value['errorType']) && $value['errorType']!=1) {
            $value['errorText'] = 'Internal Server Error';
          }
        });
      }

      if ($isFlatten) \Illuminate\Http\Resources\Json\Resource::withoutWrapping();

      if ($returnModels instanceof Illuminate\Database\Eloquent\Collection) {
        if (!$isFlatten) return new \App\Http\Resources\ExtendedResourceCollection($returnModels,$success,$errorTexts);
        else return new \Illuminate\Http\Resources\Json\ResourceCollection($returnModels);
      } else {
        if (!is_array($returnModels)) $returnModels = (array)$returnModels;
        if (!$isFlatten) return new \App\Http\Resources\ExtendedResource($returnModels,$success,$errorTexts);
        else return new \Illuminate\Http\Resources\Json\JsonResource($returnModels);
      }
    }

    public static function RemoteSOAPApi($ApiName,$CallData,$cache=0) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $CallDataHash = md5(json_encode($CallData));
      $cacheKey = $ApiName.'#'.$CallDataHash;

      if ($cache && Cache::has($cacheKey)) {
        log::info('retrieve data from cache');
        return Cache::get($cacheKey);
      }

      $api = \App\Models\Api\Apis::where('name',$ApiName)->first();
      if ($api == null) {
        $success = false;
        array_push($errorTexts,['errorText'=>"API Not Found",'errorType'=>2]);
      } else {
        $ApiUrl = $api->sourceApiUrl;
        $ApiMethod = $api->sourceApiMethod;
        $ETLCode = $api->ETLCode;
        $ETLCodeError = $api->ETLCodeError;

        return self::RemoteSOAPApiRaw($ApiUrl,$ApiMethod,$CallData,$ETLCode,$ETLCodeError,$cache);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function RemoteSOAPApiRaw($ApiUrl,$functionName,$CallData,$ETLCode=null,$ETLCodeError=null,$cache=0) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $CallDataHash = md5(json_encode($CallData));
      $cacheKey = $ApiUrl.'#'.$functionName.'#'.$CallDataHash;

      if ($cache && Cache::has($cacheKey)) {
        log::info('retrieve data from cache');
        return Cache::get($cacheKey);
      }

      $client = new \SoapClient($ApiUrl,[
          'trace'=> true,
          'exceptions' => true,
          'cache_wsdl'=> WSDL_CACHE_NONE,
          'keep-alive' => false,
          'user_agent' => 'Mozilla/1.0N (Windows)'
        ]);
      try {
        $client->__setLocation($ApiUrl);
        $ApiData = $client->__soapCall($functionName,$CallData);
        if (is_object($ApiData) && isset($ApiData->return)) $ApiData = $ApiData->return;
        $ApiData = json_decode(json_encode(simplexml_load_string($ApiData)),true);

        if (!empty($ETLCode)) eval($ETLCode);
        else $returnModels = $ApiData;

        try {
          if (!empty($ETLCode)) eval($ETLCode);
          else $returnModels = $ApiData;
        } catch(\Exception $e) {
          $success = false;
          array_push($errorTexts,['errorText'=>"Data transformation logic error (API Error)",'errorType'=>1]);
        }

        if ($success && $cache) {
          Cache::put($cacheKey,["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels],$cache);
        }
      } catch (\SoapFault $e) {
        log::error("Error calling to $functionName in $ApiUrl.",["Message"=>$e->getMessage()]);

        $success = false;

        try {
          if (!empty($ETLCodeError)) eval($ETLCodeError);
        } catch(\Exception $e) {
          array_push($errorTexts,['errorText'=>"Data transformation logic error (API Error)",'errorType'=>1]);
        }

        array_push($errorTexts,['errorText'=>$e->getMessage(),"errorType"=>2]);
      }
      
      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function version() {
      $returnModels = [
        "version" => env('APP_VERSION', 'unspecified'),
        "environment " => env('APP_ENV', 'unspecified')
      ];

      return new \App\Http\Resources\ExtendedResource($returnModels);
    }
}
