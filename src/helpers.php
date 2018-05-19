<?php

use Naxon\Errors\Contracts\ErrorLoader;

if (!function_exists('error')) {
    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return ErrorLoader|string|array|null
     */
    function error(?string $key = null, array $replace = [], ?string $locale = null)
    {
        if (is_null($key)) {
            return app('errorLoader');
        }

        return app('errorLoader')->error($key, $replace, $locale);
    }
}