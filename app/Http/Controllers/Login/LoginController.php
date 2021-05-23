<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public static function Login(Request $request) {
        $credentials = [
            'username' => ($request->data && $request->data["username"]) ? $request->data["username"] : $request->username,
            'password' => ($request->data && $request->data["password"]) ? $request->data["password"] : $request->password,
        ];

        $device = $request->device ? $request->device : "Unspecified device";

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return [
                "token" => $user->createToken($device)->plainTextToken,
                "userData" => $user
            ];
        } else {
            return response('Invalid username or password',401);
        }
    }

    public static function Logout(Request $request) {
        $currentToken = $request->user()->currentAccessToken();
        $request->user()->currentAccessToken()->delete();

        return $currentToken;
    }

    public static function LogoutAll(Request $request) {
        $allTokens = $request->user()->tokens();
        $request->user()->tokens()->delete();

        return $allTokens;
    }

    public static function User(Request $request) {
        return $request->user();
    }

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
