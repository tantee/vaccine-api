<?php

namespace App\Document;

use Illuminate\Support\Facades\Storage;

class Image
{
    public static function Base64($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        $base64string = $CurrVal;

        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];


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

        $tmpImageFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.'.\App\Utilities\File::guessExtension($mimeType));

        file_put_contents($tmpImageFile,$content);

        $CurrVal = $tmpImageFile;
      }
    }

    public static function Asset($FieldName, &$CurrVal,&$CurrPrm) {
      $tmpUniqId = uniqid();
      $tmpDirectory = $CurrPrm['tmpDirectory'];

      $asset = \App\Models\Asset\Assets::find($CurrVal);
      $tmpFilename = $tmpDirectory.'/'.$tmpUniqId.'.'.\App\Utilities\File::guessExtension($asset->mimeType);

      if ($asset !== null) {
        $content = Storage::disk($asset->storage)->get($asset->storagePath);
        Storage::put($tmpFilename,$content);

        $CurrVal = storage_path('app/'.$tmpFilename);
      }
    }
}
