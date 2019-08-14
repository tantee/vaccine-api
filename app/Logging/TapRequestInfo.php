<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;

class TapRequestInfo
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
            $record['extra']['ip'] = \Request::getClientIp();
            $record['extra']['path'] = \Request::path();
            $record['extra']['referer'] = \Request::server('HTTP_REFERER');
            $record['extra']['user'] = (Auth::guard('api')->check()) ? Auth::guard('api')->user()->username : "";

            return $record;
          });
        }
    }
}
