<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public static function verifyPassword(Request $request) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        if (isset($request->data['password'])) {
            $success = Hash::check($request->data['password'],$request->user()->password);
        } else {
            $success = false;
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
