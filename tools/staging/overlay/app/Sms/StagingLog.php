<?php

namespace App\Sms;

/** STAGING ONLY: records messages instead of sending them. */
class StagingLog
{
    private $tokens = [];

    public function tokens($t) { $this->tokens = (array) $t; return $this; }

    public function __call($name, $args)
    {
        if (strpos($name, 'send') === 0) {
            \Log::info('staging sms suppressed', ['method' => $name, 'to' => $args[0] ?? null]);
        }
        return $this;
    }

    public static function __callStatic($name, $args) { return true; }
}
