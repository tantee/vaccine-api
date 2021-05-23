<?php

namespace App\Http\Controllers\User;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public static function register($username,$password,$name,$email,$position,$licenseNo=null,$roles=null,$permissions=null) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $validatorRule = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:13|unique:users',
            'password' => 'required|string|min:6',
            'position' => 'required|string'
        ];

        $dataItem = [
            'username' => $username,
            'password' => $password,
            'name' => $name,
            'position' => $position,
        ];

        $validator = Validator::make($dataItem, $validatorRule);
        if ($validator->fails()) {
          foreach($validator->errors()->getMessages() as $key => $value) {
            foreach($value as $message) array_push($errorTexts,["errorText"=>$message]);
          }
          $success = false;
        }

        if ($success) {
            $positionData = \App\Models\Master\MasterItems::where('groupKey','$UserPosition')->where('itemCode',$position)->first();

            $newUser = new \App\Models\User\Users();

            $newUser->username = $username;
            $newUser->password = Hash::make($password);
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->position = $position;
            $newUser->licenseNo = $licenseNo;

            if ($positionData && isset($positionData->properties["defaultRoles"]) && !empty($positionData->properties["defaultRoles"])) {
                $newUser->roles = $positionData->properties["defaultRoles"];
            }

            $newUser->save();

            $returnModels = $newUser;
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function update($username,$password,$password_confirmation,$name,$email,$position,$licenseNo=null) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $validatorRule = [
            'name' => 'required|string|max:255',
            'password' => 'sometimes|string|min:6|confirmed',
            'position' => 'required|string'
        ];

        $dataItem = [
            'name' => $name,
            'position' => $position,
        ];

        if (!empty($password)) {
            $dataItem['password'] = $password;
            $dataItem['password_confirmation'] = $password_confirmation;
        }

        $validator = Validator::make($dataItem, $validatorRule);
        if ($validator->fails()) {
          foreach($validator->errors()->getMessages() as $key => $value) {
            foreach($value as $message) array_push($errorTexts,["errorText"=>$message]);
          }
          $success = false;
        }

        if ($success) {
            if (Auth::user()->username == $username) {
                $user = \App\Models\User\Users::where('username',$username)->first();
                if ($user) {
                    if ($password) $user->password = Hash::make($password);
                    $user->name = $name;
                    $user->email = $email;
                    $user->position = $position;
                    $user->licenseNo = $licenseNo;
                    $user->save();

                    $returnModels = $user;
                }
            } else {
                $success = false;
                array_push($errorTexts,["errorText" => 'Invalid username and credential']);
            }
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function changePassword($username,$password) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $validatorRule = [
            'username' => 'required|string|max:13|exists:users',
            'password' => 'required|string|min:6',
        ];

        $dataItem = [
            'username' => $username,
            'password' => $password,
        ];

        $validator = Validator::make($dataItem, $validatorRule);
        if ($validator->fails()) {
          foreach($validator->errors()->getMessages() as $key => $value) {
            foreach($value as $message) array_push($errorTexts,["errorText"=>$message]);
          }
          $success = false;
        }

        if ($success) {
            $existUser = \App\Models\User\Users::find($username);
            $existUser->password = Hash::make($password);
            $existUser->save();

            $returnModels = $existUser;
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
