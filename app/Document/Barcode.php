<?php

namespace App\Document;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Barcode
{
    public static function QrCode($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];
        $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.png');
        QrCode::encoding('UTF-8')->size(300)->margin(0)->format('png')->errorCorrection('H')->generate($CurrVal,$tmpBarcodeFile);

        $CurrVal = $tmpBarcodeFile;
      }
    }

    public static function Code39($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];
        $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.png');
        $barcode = new \Picqer\Barcode\BarcodeGeneratorPNG();
        file_put_contents($tmpBarcodeFile,$barcode->getBarcode($CurrVal, $barcode::TYPE_CODE_39));

        $CurrVal = $tmpBarcodeFile;
      }
    }

    public static function Code128($FieldName, &$CurrVal,&$CurrPrm) {
      if (isset($CurrPrm['tmpDirectory'])) {
        if (\is_array($CurrVal)) $CurrVal = json_encode($CurrVal);

        $tmpUniqId = uniqid();
        $tmpDirectory = $CurrPrm['tmpDirectory'];
        $tmpBarcodeFile = storage_path('app/'.$tmpDirectory.'/'.$tmpUniqId.'.png');
        $barcode = new \Picqer\Barcode\BarcodeGeneratorPNG();
        file_put_contents($tmpBarcodeFile,$barcode->getBarcode($CurrVal, $barcode::TYPE_CODE_128));

        $CurrVal = $tmpBarcodeFile;
      }
    }
}
