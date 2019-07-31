<?php

namespace App\Document;

use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\shared\Html;
use PhpOffice\PhpWord\Writer\Word2007\Element\Container;
use PhpOffice\Common\XMLWriter;
use PhpOffice\PhpWord\Settings;

class Format
{
    public static function fromHtml($FieldName, &$CurrVal) {
      if (self::isHtml($CurrVal)) {
        $textRun = new TextRun();

        Html::addHtml($textRun, $CurrVal);

        $xmlWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY, './', Settings::hasCompatibility());
        $container = new Container($xmlWriter,$textRun);
        $container->write();

        $CurrVal = '</w:t></w:r>'.$xmlWriter->getData().'<w:r><w:t>';
      }
    }

    private static function isHtml($str) {
      return preg_match('/<\s?[^\>]*\/?\s?>/i', $string);
    }
}
