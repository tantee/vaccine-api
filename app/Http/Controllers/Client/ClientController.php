<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public static function clientStamp($clientId) {
        $client = \App\Models\Configuration\ClientConfigurations::firstOrCreate(['clientId'=>$clientId]);
        $client->lastSeen = now();
        $client->save();

        return $client;
    }

    public static function clientSaveConfig($clientId,$configName=null,$configValue=null) {
        $client = \App\Models\Configuration\ClientConfigurations::firstOrCreate(['clientId'=>$clientId]);
        if ($configName && $configValue) {
            $tmpConfig = array_wrap($client->configuration);
            $tmpConfig[$configName] = $configValue;
            $client->configuration = $tmpConfig;
            $client->save();
        }
        return $client;
    }
}
