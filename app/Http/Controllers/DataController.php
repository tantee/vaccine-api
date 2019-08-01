<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class DataController extends Controller
{
    public static function createModel($data,$model,$validatorRule=[],$fillable=[],$parentTransaction=false) {
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
            $createdModel = $model::create($newItem)->fresh();
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

    public static function updateModel($data,$model,$returnData = false,$override = false,$validatorRule=[],$fillable=[],$parentTransaction=false) {
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
          if ($returnData) $returnModels = $tempModel->get();
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
        $return = DataController::createModel($request->data,$model,$validatorRule,$fillable);
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
        'data.keyword' => 'required',
      ]);

      if ($queryDataValidator->fails()) {
        foreach($queryDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        try {
          $searchModel = new $model;
          $searchField = (isset($request->data['field'])) ? $request->data['field'] : array_diff(Schema::getColumnListing($searchModel->getTable()),$excludedField);

          if (method_exists($searchModel,'scopeActive')) $searchModel = $searchModel->active();

          if (isset($request->data['keyword'])) {
            $returnModels = \Searchy::search($searchModel->getTable())->fields(['itemValue','itemValueEN'])->query($request->data['keyword'])->getQuery();
            if(isset($request->data['filter']) && is_array($request->data['filter'])) {
              $returnModels = $returnModels->where($request->data['filter']);
            }
            $returnModels = $returnModels->get();
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

        $return = DataController::updateModel($request->data,$model,$returnData,$override,$validatorRule,$fillable);
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
        'data.searchWhere' => 'sometimes|required|array'
      ]);

      if ($searchDataValidator->fails()) {
        foreach($searchDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        if (!array_key_exists('searchWhere',$request->data)) {
          if (array_keys($request->data) !== range(0, count($request->data) - 1)) {
            $data = $request->data;
          } else {
            $success = false;
            array_push($errorTexts,["errorText"=>"Invalid search data"]);
          }

          if ($success) {
            $tempData = [];
            foreach($data as $key=>$value) {
              array_push($tempData,['searchWhere' => [[$key,'=',$value]]]);
            }
            $data = $tempData;
          }
        } else {
          if (count($request->data['searchWhere'])!==count($request->data['searchWhere'],COUNT_RECURSIVE)) {
            $data = [];
            foreach($request->data['searchWhere'] as $value) array_push($data,['searchWhere' => [$value]]);
          } else {
            $data = [$request->data];
          }
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
          foreach($data as $row) {
            $column = explode('$',$row['searchWhere'][0][0]);
            if (count($column)==1) {
              $returnModels = $returnModels->Where($row['searchWhere']);
            } else {
              $row['searchWhere'][0][0] = $column[count($column)-1];
              $returnModels = $returnModels->whereHas($column[0],function($query) use ($row) {
                $query->where($row['searchWhere']);
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
}