<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public static function clientStamp($clientId) {
        $client = \App\Models\Client\Clients::firstOrCreate(['clientId'=>trim($clientId)]);
        $client->lastSeen = now();
        $client->save();

        return $client;
    }

    public static function clientSaveConfig($clientId,$configName=null,$configValue=null) {
        $client = \App\Models\Client\Clients::firstOrCreate(['clientId'=>trim($clientId)]);
        if ($configName && $configValue) {
            $tmpConfig = Arr::wrap($client->configuration);
            $tmpConfig[$configName] = $configValue;
            $client->configuration = $tmpConfig;
            $client->save();
        }
        return $client;
    }
}
