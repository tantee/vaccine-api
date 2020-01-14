<?php

namespace App\Document;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Barcode
{
    public static function QrCode($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];

        if (!empty($CurrVal)) {
          if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.svg');
          QrCode::encoding('UTF-8')->size(300)->margin(0)->generate($CurrVal,$tmpBarcodeFile);
        } else {
          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.jpg');

          $img = imagecreatetruecolor(1, 1);
          $bg = imagecolorallocate ( $img, 255, 255, 255 );
          imagefilledrectangle($img,0,0,1,1,$bg);

          imagejpeg($img,$tmpBarcodeFile,100);
        }
        
        $CurrVal = $tmpBarcodeFile;
      }
    }

    public static function Code39($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];

        if (!empty($CurrVal)) {
          if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.svg');
          $barcode = new \Picqer\Barcode\BarcodeGeneratorSVG();
          file_put_contents($tmpBarcodeFile,$barcode->getBarcode($CurrVal, $barcode::TYPE_CODE_39));
        } else {
          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.jpg');

          $img = imagecreatetruecolor(1, 1);
          $bg = imagecolorallocate ( $img, 255, 255, 255 );
          imagefilledrectangle($img,0,0,1,1,$bg);

          imagejpeg($img,$tmpBarcodeFile,100);
        }

        $CurrVal = $tmpBarcodeFile;
      }
    }

    public static function Code128($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];

        if (!empty($CurrVal)) {
          if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.svg');
          $barcode = new \Picqer\Barcode\BarcodeGeneratorSVG();
          file_put_contents($tmpBarcodeFile,$barcode->getBarcode($CurrVal, $barcode::TYPE_CODE_128));
        } else {
          $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.jpg');

          $img = imagecreatetruecolor(1, 1);
          $bg = imagecolorallocate ( $img, 255, 255, 255 );
          imagefilledrectangle($img,0,0,1,1,$bg);

          imagejpeg($img,$tmpBarcodeFile,100);
        }

        $CurrVal = $tmpBarcodeFile;
      }
    }
}
