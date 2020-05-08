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
            $record['context']['call_ip'] = \Request::getClientIp();
            $record['context']['call_path'] = \Request::path();
            $record['context']['call_referer'] = \Request::server('HTTP_REFERER');
            $record['context']['call_user'] = (Auth::guard('api')->check()) ? Auth::guard('api')->user()->username : "";

            return $record;
          });
        }
    }
}
