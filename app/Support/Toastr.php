<?php

namespace App\Support;

/**
 * Compatibility wrapper for legacy Yoeunes\Toastr API after switching to php-flasher.
 */
class Toastr
{
    public static function success($message = null, $title = null, array $options = [])
    {
        return toastr()->success((string) $message, (string) ($title ?? ''), $options);
    }

    public static function info($message = null, $title = null, array $options = [])
    {
        return toastr()->info((string) $message, (string) ($title ?? ''), $options);
    }

    public static function warning($message = null, $title = null, array $options = [])
    {
        return toastr()->warning((string) $message, (string) ($title ?? ''), $options);
    }

    public static function error($message = null, $title = null, array $options = [])
    {
        return toastr()->error((string) $message, (string) ($title ?? ''), $options);
    }
}
