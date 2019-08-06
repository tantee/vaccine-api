<?php

namespace App\Http\Controllers\Asset;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;

class AssetController extends Controller
{
    public static function addAssetBase64($hn,$base64string,$assetType=null,$storage='local') {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $DataValidator = Validator::make(['hn'=>$hn,'base64string'=>$base64string],[
        'hn' => 'required',
        'base64string' => 'required',
      ]);

      if ($DataValidator->fails()) {
        foreach($DataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        try {
          $data = explode(',',$base64string);
          if (count($data)==1) {
            $content = $data[0];
          } else {
            $mimeType = explode(';',$data[0]);
            $mimeType = explode(':',$mimeType[0]);
            if (count($mimeType)==1) $mimeType=$mimeType[0];
            else $mimeType=$mimeType[1];

            $content = $data[1];
          }

          $content = base64_decode($content);

          if (!isset($mimeType)) $mimeType = finfo_buffer(finfo_open(), $content, FILEINFO_MIME_TYPE);

          $returnModels = AssetController::addAsset($hn,$content,$assetType,$mimeType,$storage);
        } catch (\Exception $e) {
          $returnModels = [];
          $success = false;
          array_push($errorTexts,["errorText" => $e->getMessage()]);
        }
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    private static function addAsset($hn, $content, $assetType=null, $mimeType=null, $storage='local') {
      $returnData = [];

      if (isset($hn) && isset($content)) {
        if (!isset($mimeType)) $mimeType = finfo_buffer(finfo_open(), $content, FILEINFO_MIME_TYPE);

        $md5hash = md5($content);
        $filepath = '/assets/'.$md5hash[0].'/'.$md5hash[1].'/'.uniqid();

        if (Storage::disk($storage)->put($filepath,$content)) {

          $data = [
            'hn' => $hn,
            'assetType' => $assetType,
            'mimeType' => $mimeType,
            'storage' => $storage,
            'storagePath' => $filepath,
            'md5hash' => $md5hash,
          ];

          $assets = DataController::createModel($data,\App\Models\Asset\Assets::class);
          if ($assets['success']) {
            foreach($assets['returnModels'] as $asset) {
              array_push($returnData,$asset->makeHidden(['storage','storagePath']));
            }
          }
        }

      }
      return $returnData;
    }

    public static function getAssetBase64($id,$md5hash=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $asset = \App\Models\Asset\Assets::find($id);
      if (($asset !== null) && (($asset->md5hash == $md5hash) || ($md5hash == null))) {
        $asset->with(['base64data']);
        $returnModels = $asset->only(['id','md5hash','base64data']);
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'Asset ID '.$id.' is not found']);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getAssetsBase64ByType($hn,$assetType) {
      $returnModels = [];
      $assets = \App\Models\Asset\Assets::where('hn',$hn)->where('assetType',$assetType)->get();
      foreach($assets as $asset) {
        $returnData = self::getAssetDataBase64($asset);
        if ($returnData !== null) $returnModels[] = $returnData;
      }
      return $returnModels;
    }

    public static function getAssetDataBase64(\App\Models\Asset\Assets $asset) {
      $returnData = null;

      if (Storage::disk($asset->storage)->exists($asset->storagePath)) {
        $returnData = Storage::disk($asset->storage)->get($asset->storagePath);
        $returnData = base64_encode($returnData);
        $returnData = 'data:'.$asset->mimeType.';base64,'.$returnData;
      }

      return $returnData;
    }

    public static function deleteAsset($id,$md5hash) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $asset = \App\Models\Asset\Assets::find($id);
      if (($asset !== null) && ($asset->md5hash == $md5hash)) {
        $asset->delete();
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No asset matached']);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
