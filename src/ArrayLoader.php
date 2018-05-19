<?php

namespace Naxon\Errors;

use Naxon\Errors\Contracts\Loader;

class ArrayLoader implements Loader
{
    /**
     * All of the error messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     * @return array
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        $namespace = $namespace ?: '*';

        if (isset($this->messages[$namespace][$locale][$group])) {
            return $this->messages[$namespace][$locale][$group];
        }

        return [];
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
        //
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath(string $path): void
    {
        //
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces(): array
    {
        //
    }

    /**
     * Add messages to the loader.
     *
     * @param string $locale
     * @param string $group
     * @param array $messages
     * @param string|null $namespace
     * @return ArrayLoader
     */
    public function addMessages(string $locale, string $group, array $messages, ?string $namespace = null): self
    {
        $namespace = $namespace ?: '*';

        $this->messages[$namespace][$locale][$group] = $messages;

        return $this;
    }
}
