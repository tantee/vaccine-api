<?php

namespace App\Http\Controllers;

use Log;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\GenericAPIController;
use App\Utilities\ArrayType;

class DataController extends Controller
{
    public static function createModel($data,$model,$validatorRule=[],$fillable=[],$parentTransaction=false,$returnWith=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if ($success) {
        if (array_keys($data) !== range(0, count($data) - 1)) $data = array($data);
      }

      if ($success && !empty($validatorRule)) {
        foreach ($data as $rows => $dataItem) {
          $validator = Validator::make($dataItem, $validatorRule);
          if ($validator->fails()) {
            foreach($validator->errors()->getMessages() as $key => $value) {
              foreach($value as $message) array_push($errorTexts,["errorText"=>$message,"field" => $key,"rows"=>$rows]);
            }
            $success = false;
          }
        }
      }

      if ($success && empty($fillable)) {
        $tempModel = new $model;
        $fillable =  Schema::getColumnListing($tempModel->getTable());
        if ($tempModel->getFillable() != []) {
          $fillable = array_intersect($fillable,$tempModel->getFillable());
        } else {
          $fillable = array_diff($fillable,$tempModel->getGuarded());
        }
      }

      if ($success) {
        if (!$parentTransaction) DB::beginTransaction();
        try {
          foreach ($data as $dataItem) {
            $newItem = array_only($dataItem,$fillable);
            $createdModel = $model::create($newItem);
            if ($returnWith!=null) $createdModel->with($returnWith);
            $createdModel->fresh();
            array_push($returnModels,$createdModel);
          }
          $success = true;
        } catch (\Exception $e) {
          if (!$parentTransaction) DB::rollBack();
          $returnModels = [];
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
        if (!$parentTransaction) DB::commit();
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function replaceModel($data,$model,$validatorRule=[],$fillable=[],$parentTransaction=false,$returnWith=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];
      $keyField = "";

      if ($success) {
        if (array_keys($data) !== range(0, count($data) - 1)) $data = array($data);
      }

      if ($success && !empty($validatorRule)) {
        foreach ($data as $rows => $dataItem) {
          $validator = Validator::make($dataItem, $validatorRule);
          if ($validator->fails()) {
            foreach($validator->errors()->getMessages() as $key => $value) {
              foreach($value as $message) array_push($errorTexts,["errorText"=>$message,"field" => $key,"rows"=>$rows]);
            }
            $success = false;
          }
        }
      }

      if ($success) {
        $tempModel = new $model;
        $keyField = $tempModel->getKeyName();
      }

      if ($success && empty($fillable)) {
        $tempModel = new $model;
        $fillable =  Schema::getColumnListing($tempModel->getTable());
        if ($tempModel->getFillable() != []) {
          $fillable = array_intersect($fillable,$tempModel->getFillable());
        } else {
          $fillable = array_diff($fillable,$tempModel->getGuarded());
        }
      }

      if ($success) {
        if (!$parentTransaction) DB::beginTransaction();
        try {
          foreach ($data as $dataItem) {
            array_push($errorTexts,["errorText" => "in replace"]);
            array_push($errorTexts,["errorText" => $keyField]);
            array_push($errorTexts,["errorText" => $data]);
            $newItem = array_only($dataItem,$fillable);
            if (isset($data[$keyField]) && $data[$keyField]!=null) {
              array_push($errorTexts,["errorText" => "in old item"]);
              $existModel = $model::find($data[$keyField]);
              if ($existModel != null) {
                $existModel->fill($newItem);
                $existModel->save();
                if ($returnWith!=null) $existModel->with($returnWith);
                $existModel->fresh();
                array_push($returnModels,$existModel);
              }
            } else {
              $createdModel = $model::create($newItem);
              if ($returnWith!=null) $createdModel->with($returnWith);
              $createdModel->fresh();
              array_push($returnModels,$createdModel);
            }
          }
          $success = true;
        } catch (\Exception $e) {
          if (!$parentTransaction) DB::rollBack();
          $returnModels = [];
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
        if (!$parentTransaction) DB::commit();
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function updateModel($data,$model,$returnData = false,$override = false,$validatorRule=[],$fillable=[],$parentTransaction=false,$returnWith=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if ($success) {
        if (!array_key_exists('updateWhere',$data)) {
          if (array_keys($data) !== range(0, count($data) - 1)) $data = array($data);

          $tempModel = new $model;
          $keyField = $tempModel->getKeyName();

          $keyFieldValidator = validator::make($data,[
            '*.'.$keyField => 'required'
          ]);

          if ($keyFieldValidator->fails()) {
            foreach($keyFieldValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
            $success = false;
          }

          if ($success) {
            $tempData = [];
            foreach($data as $row) {

              if (!$override && isset($row['updated_at']) && !DataController::isCurrentRecord($model,$row[$keyField],$row['updated_at'])) {
                $success = false;
                array_push($errorTexts,["errorText"=>"Data changed before update"]);
                break;
              }

              $tempUpdate = [];
              $tempUpdate = array_add($tempUpdate,'updateWhere',[$keyField => array_pull($row,$keyField)]);
              $tempUpdate = array_add($tempUpdate,'updateValue',$row);
              array_push($tempData,$tempUpdate);
            }
            $data = $tempData;
          }
        } else {
          $data = [$data];
        }
      }

      if ($success && empty($fillable)) {
        $tempModel = new $model;
        $fillable =  Schema::getColumnListing($tempModel->getTable());
        if ($tempModel->getFillable() != []) {
          $fillable = array_intersect($fillable,$tempModel->getFillable());
        } else {
          $fillable = array_diff($fillable,$tempModel->getGuarded());
        }
      }

      if ($success && !empty($validatorRule)) {
        array_push($errorTexts,$data);
        foreach($data as $row) {
          $validator = Validator::make($row['updateValue'], $validatorRule);
          if ($validator->fails()) {
            foreach($validator->errors()->getMessages() as $key => $value) {
              foreach($value as $message) array_push($errorTexts,["errorText"=>$message,"field" => $key]);
            }
            $success = false;
          }
        }
      }

      if ($success) {
        if (!$parentTransaction) DB::beginTransaction();
        try {
          $tempModel = new $model;
          foreach($data as $row) {
            $newValue = array_only($row["updateValue"],$fillable);
            $tempUpdating = $model::where($row['updateWhere'])->get();
            foreach($tempUpdating as $item) {
              $item->fill($newValue);
              $item->save();
            };
            $tempModel = $tempModel->orWhere($row['updateWhere']);
          }
          if ($returnData) {
            if ($returnWith!=null) $tempModel->with($returnWith);
            $returnModels = $tempModel->get();
          }
        } catch (\Exception $e) {
          if (!$parentTransaction) DB::rollBack();
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
        if (!$parentTransaction) DB::commit();
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function createModelByRequest(Request $request,$model,$validatorRule=[],$fillable=[]) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $createDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
      ]);

      if ($createDataValidator->fails()) {
        foreach($createDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        $return = DataController::createModel($request->data,$model,$validatorRule,$fillable,false,(isset($request->with)) ? $request->with : null);
        $success = $return["success"];
        $errorTexts = $return["errorTexts"];
        $returnModels = $return["returnModels"];
      }

      return new \App\Http\Resources\ExtendedResourceCollection(collect($returnModels),$success,$errorTexts);
    }

    public static function replaceModelByRequest(Request $request,$model,$validatorRule=[],$fillable=[]) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $createDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
      ]);

      if ($createDataValidator->fails()) {
        foreach($createDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        $return = DataController::replaceModel($request->data,$model,$validatorRule,$fillable,false,(isset($request->with)) ? $request->with : null);
        $success = $return["success"];
        $errorTexts = $return["errorTexts"];
        $returnModels = $return["returnModels"];
      }

      return new \App\Http\Resources\ExtendedResourceCollection(collect($returnModels),$success,$errorTexts);
    }

    public static function readModelByRequest(Request $request,$model) {
      $success = true;
      $errorTexts = [];
      $returnModels = collect();

      if ($success) {
        try {
          $returnModels = new $model;

          if (isset($request->key)) $returnModels = $returnModels->find($request->key);

          if (isset($request->scope)) $returnModels = $returnModels->{$request->scope}();

          if (isset($request->with)) $returnModels = $returnModels->with($request->with);

          if (!isset($request->key)) {
            if (isset($request->orderBy)) {
              $orderBy = explode(",",$request->orderBy,2);
              if (count($orderBy)==1) array_push($orderBy,"ASC");
              $returnModels = $returnModels->orderBy($orderBy[0],$orderBy[1]);
            }

            if (isset($request->perPage) && is_numeric($request->perPage)) {
              $returnModels = $returnModels->paginate($request->perPage)->appends(['perPage'=>$request->perPage]);
              if (isset($request->orderBy)) $returnModels->appends(['orderBy'=>$request->orderBy]);
              if (isset($request->with)) $returnModels->appends(['with'=>$request->with]);
              if (isset($request->scope)) $returnModels->appends(['scope'=>$request->scope]);
            }
            else $returnModels = $returnModels->get();
          }
        } catch (\Exception $e) {
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
      }
      return GenericApiController::resultToResource(["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels]);
    }

    public static function queryModelByRequest(Request $request,$model) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $excludedField = ['created_at','updated_at','deleted_at','created_by','updated_by','deleted_by'];

      $queryDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
      ]);

      if ($queryDataValidator->fails()) {
        foreach($queryDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        try {
          $searchModel = new $model;
          $searchField = (isset($request->data['field'])) ? $request->data['field'] : array_diff(Schema::getColumnListing($searchModel->getTable()),$excludedField);

          if (isset($request->data['keyword'])) {
            $returnModels = \Searchy::search($searchModel->getTable())->fields($searchField)->query($request->data['keyword'])->getQuery();
            if(isset($request->data['filter']) && is_array($request->data['filter'])) {
              $returnModels = $returnModels->where($request->data['filter']);
            }
            if (method_exists($searchModel,'scopeActive')) {
              $searchModel = $searchModel->active();
              $returnModels->mergeWheres($searchModel->getQuery()->wheres, $searchModel->getQuery()->bindings);
            }
            $returnModels = $model::hydrate($returnModels->get()->toArray())->fresh();
          } else {
            if (method_exists($searchModel,'scopeActive')) $searchModel = $searchModel->active();            
            if (isset($request->data['all']) && $request->data['all']) {   
              if(isset($request->data['filter']) && is_array($request->data['filter'])) {
                $returnModels = $searchModel->where($request->data['filter']);
                $returnModels = $returnModels->get();
              } else {
                $returnModels = $searchModel::all();
              }
            }
          }
        } catch (\Exception $e) {
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
      }
      return GenericApiController::resultToResource(["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels]);
    }

    public static function updateModelByRequest(Request $request,$model,$validatorRule=[],$fillable=[]) {
      $success = true;
      $errorTexts = [];
      $returnData = false;
      $override = false;
      $returnModels = [];

      $tempModel = new $model;
      $keyField = $tempModel->getKeyName();

      $updateDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
        'data.updateWhere' => 'sometimes|required|array',
        'data.updateValue' => 'required_with:updateWhere|array',
        'returnData' => 'sometimes|required|boolean',
        'override' => 'sometimes|required|boolean',
      ]);

      if ($updateDataValidator->fails()) {
        foreach($updateDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        if (isset($request->returnData)) $returnData = $request->returnData;
        if (isset($request->override)) $override = $request->override;

        $return = DataController::updateModel($request->data,$model,$returnData,$override,$validatorRule,$fillable,false,(isset($request->with)) ? $request->with : null);
        $success = $return["success"];
        $errorTexts = $return["errorTexts"];
        $returnModels = $return["returnModels"];
      }

      return new \App\Http\Resources\ExtendedResourceCollection(collect($returnModels),$success,$errorTexts);
    }

    public static function searchModelByRequest(Request $request,$model,$resource=\App\Http\Resources\ExtendedResourceCollection::class) {
      $success = true;
      $errorTexts = [];
      $returnModels = collect();

      $searchDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
      ]);

      if ($searchDataValidator->fails()) {
        foreach($searchDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }


      if ($success) {
        $data = [];

        if (ArrayType::isAssociative($request->data)) {
          foreach($request->data as $key=>$value) {
            $explodedKey = explode('#',$key);
            if (count($explodedKey)==3) {
              $key = $explodedKey[0]."#".$explodedKey[1];
              array_push($data,[$key,$explodedKey[2],$value]);
            } else {
              array_push($data,[$key,'=',$value]);
            }
          }
        } else {
          if (ArrayType::isMultiDimension($request->data)) $data = $request->data;
          else $data = [$request->data];
        }

        $searchDataValidator = Validator::make($data,[
          '*' => 'array|size:3',
        ]);

        if ($searchDataValidator->fails()) {
          foreach($searchDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
          $success = false;
        }
      }

      if ($success) {
        if (!is_subclass_of($resource,\App\Http\Resources\ExtendedResourceCollection::class) && $resource != \App\Http\Resources\ExtendedResourceCollection::class) {
          $resource = \App\Http\Resources\ExtendedResourceCollection::class;
        }
      }

      if ($success) {
        try {
          $returnModels = new $model;
          if (isset($request->scope)) $returnModels = $returnModels->{$request->scope}();
          
          foreach($data as $row) {
            $column = explode('$',$row[0]);
            if (count($column)==1) {
              $returnModels = self::searchQuery($returnModels,$row);
            } else {
              $row[0] = $column[count($column)-1];
              $returnModels = $returnModels->whereHas($column[0],function($query) use ($row) {
                DataController::searchQuery($query,$row);
              });
            }
          }

          if (isset($request->with)) $returnModels = $returnModels->with($request->with);
          if (isset($request->orderBy)) {
            $orderBy = explode(",",$request->orderBy,2);
            if (count($orderBy)==1) array_push($orderBy,"ASC");
            $returnModels = $returnModels->orderBy($orderBy[0],$orderBy[1]);
          }
          
          if (isset($request->perPage) && is_numeric($request->perPage)) {
            $returnModels = $returnModels->paginate($request->perPage)->appends(['perPage'=>$request->perPage]);
            if (isset($request->orderBy)) $returnModels->appends(['orderBy'=>$request->orderBy]);
            if (isset($request->with)) $returnModels->appends(['with'=>$request->with]);
          }
          else $returnModels = $returnModels->get();

        } catch (\Exception $e) {
          $success = false;
          $returnModels = [];
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
      }
      return new $resource($returnModels,$success,$errorTexts);
    }

    public static function deleteModelByRequest(Request $request,$model) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $data = $request->data;

      $tempModel = new $model;
      $keyField = $tempModel->getKeyName();

      $deleteDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
        'data.'.$keyField => 'required',
      ]);

      if ($deleteDataValidator->fails()) {
        foreach($deleteDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        DB::beginTransaction();
        try {
          $model::destroy($data[$keyField]);
        } catch (\Exception $e) {
          DB::rollBack();
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
        DB::commit();
      }

      return new \App\Http\Resources\ExtendedResourceCollection(collect($returnModels),$success,$errorTexts);
    }

    public static function findModelByRequest(Request $request,$model) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $queryDataValidator = Validator::make($request->all(),[
        'data' => 'required|array',
        'data.key' => 'required',
      ]);

      if ($queryDataValidator->fails()) {
        foreach($queryDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        try {
          $tempModel = new $model;
          if (\is_array($request->data['key'])) $returnModels = $tempModel->where($request->data['key'])->firstOrFail();
          else $returnModels = $tempModel->findOrFail($request->data['key']);

        } catch (\Exception $e) {
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
      }
      return GenericApiController::resultToResource(["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels]);
    }

    public static function isCurrentRecord($model,$modelId,$updated_at) {
      try {
        $record = $model::findOrFail($modelId);
        return ($record->updated_at == $updated_at);
      } catch (\Exception $e) {
        return false;
      }
    }

    public static function searchQuery(&$query, $searchData) {
      $singleParameter = ["whereNull","whereNotNull","orWhereNull","orWhereNotNull","whereHas"];

      $whereFunction = explode('#',$searchData[0]);
      if (count($whereFunction)>1) {
        $searchData[0] = $whereFunction[count($whereFunction)-1];
        $whereFunction = $whereFunction[0];
      } else {
        if (is_array($searchData[2])) $whereFunction = 'whereIn';
        else $whereFunction = 'Where';
      }
      
      if (in_array($whereFunction,$singleParameter)) return $query->$whereFunction($searchData[0]);
      else if ($searchData[1]=="=") return $query->$whereFunction($searchData[0],$searchData[2]);
      else return $query->$whereFunction($searchData[0],$searchData[1],$searchData[2]);
    }
}
