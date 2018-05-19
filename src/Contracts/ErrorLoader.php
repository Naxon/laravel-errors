<?php

namespace Naxon\Errors\Contracts;

interface ErrorLoader
{
    /**
     * Get the error for a given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return mixed
     */
    public function error(string $key, array $replace = [], string $locale = null);

    /**
     * Get a error according to an integer value.
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function errorChoice(string $key, $number, array $replace = [], ?string $locale = null): string;

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale(string $locale): void;
}
