<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GenericAPIController extends Controller
{
    public static function route(Request $request,$methodNamespace,$methodClass,$methodName,$customParameters=[],$directReturn=false) {
      $methodClassName = "\\App\\Http\\".(($methodNamespace) ? "Controllers\\$methodNamespace\\" : "Controllers\\")."$methodClass";

      if (method_exists($methodClassName,$methodName)) {
        $method = new \ReflectionMethod($methodClassName,$methodName);
        $parameters = $method->getParameters();

        $callParameters = [];

        foreach($parameters as $parameter) {
          if ($parameter->getClass()!=null && $parameter->getClass()->name == "Illuminate\\Http\\Request") {
            array_push($callParameters,$request);
          } else {
            if (isset($customParameters[$parameter->name])) array_push($callParameters,$customParameters[$parameter->name]);
            else if (isset($request->data[$parameter->name])) array_push($callParameters,$request->data[$parameter->name]);
            else if (isset($request->data) && ($parameter->name=='data')) array_push($callParameters,$request->data);
            else if ($request->has($parameter->name)) array_push($callParameters,$request->input($parameter->name));
            else if ($parameter->isDefaultValueAvailable()) array_push($callParameters,$parameter->getDefaultValue());
            else array_push($callParameters,null);
          }
        }

        if (!$directReturn) {
          $returnResult = $methodClassName::$methodName(...$callParameters);

          return self::resultToResource($returnResult);
        } else {
          return $methodClassName::$methodName(...$callParameters);
        }
      } else {
        return response("Method not found",404);
      }
    }

    public static function routeDirect(Request $request,$methodNamespace,$methodClass,$methodName) {
      return self::route($request,$methodNamespace,$methodClass,$methodName,true);
    }

    public static function resultToResource($result) {
      if (is_array($result)) {
        if (isset($result['returnModels']) && isset($result['success'])) {
          if ($result['returnModels'] instanceof Illuminate\Database\Eloquent\Collection) {
            return new \App\Http\Resources\ExtendedResourceCollection($result['returnModels'],$result['success'],$result['errorTexts']);
          } else {
            return new \App\Http\Resources\ExtendedResource($result['returnModels'],$result['success'],$result['errorTexts']);
          }
        } else {
          return new \App\Http\Resources\ExtendedResource($result);
        }
      } else {
        if ($result instanceof Illuminate\Database\Eloquent\Collection) {
          return new \App\Http\Resources\ExtendedResourceCollection($result);
        } else {
          return new \App\Http\Resources\ExtendedResource($result);
        }
      }
    }
}
