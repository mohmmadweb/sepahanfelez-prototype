<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/** STAGING ONLY: no SMS leaves this server; messages go to the log. */
class SMS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return new \App\Sms\StagingLog;
    }
}
