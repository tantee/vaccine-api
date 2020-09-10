<?php

namespace App\Http\Controllers\Stock;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;

class StockController extends Controller
{
    public static function dispensingFromRequest($stocksRequestId) {
        $dispensings = [];

        $request = \App\Models\Stock\StocksRequests::find($stocksRequestId);
        if ($request) {
            foreach($request->requestData as $requestItem) {
                $dispensings[] = [
                    "productCode" => $requestItem["productCode"],
                    "quantity" => $requestItem["quantity"],
                    "stockFrom" => $request->stockFrom,
                    "stockTo" => $request->stockTo,
                ];
            }

            return DataController::createModel($dispensings,\App\Models\Stock\StocksDispensings::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid StocksRequest ID"]], "returnModels" => []];
        }        
    }

    public static function dispenseStocksRequest($stocksRequestId) {
        $dispensings = \App\Models\Stock\StocksDispensings::where('stocksRequestId',$stocksRequestId)->get();
        foreach($dispensings as $dispensing) {
            if ($dispensing->status == 'prepared') {
                $dispensing->status = "dispensed";
                $dispensing->save();
            }
        }
        return $dispensings;
    }
}
