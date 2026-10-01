<?php

namespace App\Sms;

use Illuminate\Support\Facades\DB;

/**
 * STAGING ONLY: records messages instead of sending them.
 *
 * Everything else in the login/registration flow stays the backend's own:
 * the controller generates the code, stores it in `verificationcodes` with
 * its 2-minute expiry, and checks it on the verify page. When STAGING_OTP is
 * set (e.g. 123456), the code the controller just stored for that mobile is
 * replaced by it at the moment it would have been texted, so testers can log
 * in with a known code. No SMS ever leaves the server.
 */
class StagingLog
{
    private $tokens = [];

    public function tokens($t) { $this->tokens = (array) $t; return $this; }

    public function __call($name, $args)
    {
        if (strpos($name, 'send') !== 0) {
            return $this;          // template(), etc.: keep chaining
        }
        $to = $args[0] ?? null;
        $fixed = (string) env('STAGING_OTP', '');
        $token = $this->tokens['token'] ?? null;
        if ($fixed !== '' && $to && $token !== null) {
            DB::table('verificationcodes')->where('mobile', $to)->where('code', $token)->update(['code' => $fixed]);
        }
        \Log::info('staging sms suppressed', ['method' => $name, 'to' => $to, 'fixed_otp' => $fixed !== '' && $token !== null]);

        return true;
    }

    public static function __callStatic($name, $args) { return true; }
}
