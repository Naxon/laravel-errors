<?php

namespace Naxon\Errors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Naxon\Errors\Contracts\Loader;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\NamespacedItemResolver;
use Naxon\Errors\Contracts\ErrorLoader as ErrorLoaderContract;

class ErrorLoader extends NamespacedItemResolver implements ErrorLoaderContract
{
    use Macroable;

    /**
     * The loader implementation.
     *
     * @var Loader
     */
    protected $loader;

    /**
     * The default locale being used by the error loader.
     *
     * @var string
     */
    protected $locale;

    /**
     * The fallback locale used by the error loader.
     *
     * @var string
     */
    protected $fallback;

    /**
     * The array of loaded error groups.
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * The message selector.
     *
     * @var MessageSelector
     */
    protected $selector;

    /**
     * ErrorLoader constructor.
     *
     * @param Loader $loader
     * @param string $locale
     */
    public function __construct(Loader $loader, string $locale)
    {
        $this->loader = $loader;
        $this->locale = $locale;
    }

    /**
     * Determine if an error exists for a given locale.
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function hasForLocale(string $key, ?string $locale = null): bool
    {
        return $this->has($key, $locale, false);
    }

    /**
     * Determine if an error exists.
     *
     * @param string $key
     * @param string|null $locale
     * @param bool $fallback
     * @return bool
     */
    public function has(string $key, string $locale = null, bool $fallback = true): bool
    {
        return $this->get($key, $locale, $fallback) !== $key;
    }

    /**
     * Get the error for a given key.
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return mixed
     */
    public function error(string $key, array $replace = [], ?string $locale = null)
    {
        return $this->get($key, $replace, $locale);
    }

    /**
     * Get the error for the given key.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @param bool $fallback
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        $locales = $fallback ? $this->localeArray($locale) : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            if (! is_null($line = $this->getLine($namespace, $group, $locale, $item, $replace))) {
                break;
            }
        }

        if (isset($line)) {
            return $line;
        }

        return $key;
    }

    /**
     * Get the error for a given key from the JSON error files.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public function getFromJson(string $key, array $replace = [], ?string $locale = null)
    {
        $locale = $locale ?: $this->locale;

        $this->load('*', '*', $locale);

        $line = $this->loaded['*']['*'][$locale][$key] ?? null;

        if (! isset($line)) {
            $fallback = $this->get($key, $replace, $locale);

            if ($fallback !== $key) {
                return $fallback;
            }
        }

        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Get an error according to an integer value.
     *
     * @param string $key
     * @param array|\Countable|int $number
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public function errorChoice(string $key, $number, array $replace = [], ?string $locale = null): string
    {
        return $this->choice($key, $number, $replace, $locale);
    }

    /**
     * Get an error according to an integer value.
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function choice(string $key, $number, array $replace = [], ?string $locale = null): string
    {
        $line = $this->get($key, $replace, $locale = $this->localeForChoice($locale));

        if (is_array($number) || $number instanceof \Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        return $this->makeReplacements($this->getSelector()->choose($line, $number, $locale), $replace);
    }

    /**
     * Get the proper locale for a choice operation.
     *
     * @param  string|null  $locale
     * @return string
     */
    protected function localeForChoice(string $locale): string
    {
        return $locale ?: $this->locale ?: $this->fallback;
    }

    /**
     * Retrieve a language line out the loaded array.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @param  array   $replace
     * @return string|array|null
     */
    protected function getLine(string $namespace, string $group, string $locale, string $item, array $replace)
    {
        $this->load($namespace, $group, $locale);

        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $line;
        }
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string  $line
     * @param  array   $replace
     * @return string
     */
    protected function makeReplacements(string $line, array $replace): string
    {
        if (empty($replace)) {
            return $line;
        }

        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Sort the replacements array.
     *
     * @param  array  $replace
     * @return array
     */
    protected function sortReplacements(array $replace): array
    {
        return (new Collection($replace))->sortBy(function ($value, $key) {
            return mb_strlen($key) * -1;
        })->all();
    }

    /**
     * Add error lines to the given locale.
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $namespace
     * @return void
     */
    public function addLines(array $lines, string $locale, string $namespace = '*'): void
    {
        foreach ($lines as $key => $value) {
            list($group, $item) = explode('.', $key, 2);

            Arr::set($this->loaded, "$namespace.$group.$locale.$item", $value);
        }
    }

    /**
     * Load the specified language group.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load(string $namespace, string $group, string $locale): void
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded(string $namespace, string $group, string $locale): bool
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace(string $namespace, string $hint): void
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath(string $path): void
    {
        $this->loader->addJsonPath($path);
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = parent::parseKey($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    /**
     * Get the array of locales to be checked.
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray(?string $locale): array
    {
        return array_filter([$locale ?: $this->locale, $this->fallback]);
    }

    /**
     * Get the message selector instance.
     *
     * @return MessageSelector
     */
    public function getSelector(): MessageSelector
    {
        if (! isset($this->selector)) {
            $this->selector = new MessageSelector;
        }

        return $this->selector;
    }

    /**
     * Set the message selector instance.
     *
     * @param  MessageSelector  $selector
     * @return void
     */
    public function setSelector(MessageSelector $selector): void
    {
        $this->selector = $selector;
    }

    /**
     * Get the language line loader implementation.
     *
     * @return Loader
     */
    public function getLoader(): Loader
    {
        return $this->loader;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function locale(): string
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get the fallback locale being used.
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback(string $fallback): void
    {
        $this->fallback = $fallback;
    }

    /**
     * Set the loaded error groups.
     *
     * @param  array  $loaded
     * @return void
     */
    public function setLoaded(array $loaded): void
    {
        $this->loaded = $loaded;
    }
}
