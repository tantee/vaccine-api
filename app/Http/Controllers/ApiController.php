<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public static function RemoteRESTApiRequest(Request $request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$ETLCodeError,$isFlatten,$isMaskError,$args) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];
      $phrMrn = '';

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
            $success = array_pull($ApiData,'success',$success);
            $errorTexts = array_pull($ApiData,'errorTexts',$errorTexts);

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

    public static function RemoteSOAPApiRequest(Request $request,$ApiName,$ApiMethod,$ApiUrl,$ETLCode,$args) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $client = new SoapClient($ApiUrl);
      $params = [];
      try {
        $ApiData = $client->__soapCall($ApiMethod,array($params));

        if (!empty($ETLCode)) eval($ETLCode);
        else $returnModels = $ApiData;
      } catch (\SoapFault $e) {
        log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

        $success = false;
        array_push($errorTexts,['errorText'=>$e->getMessage(),"errorType"=>2]);
      }

      if ($returnModels instanceof Illuminate\Database\Eloquent\Collection) {
        return new \App\Http\Resources\ExtendedResourceCollection($returnModels,$success,$errorTexts);
      } else {
        if (!is_array($returnModels)) $returnModels = (array)$returnModels;
        return new \App\Http\Resources\ExtendedResource($returnModels,$success,$errorTexts);
      }
    }

    public static function version() {
      $returnModels = [
        "version" => env('APP_VERSION', 'unspecified'),
        "environment " => env('APP_ENV', 'unspecified')
      ];

      return new \App\Http\Resources\ExtendedResource($returnModels);
    }
}
