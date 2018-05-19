<?php

namespace Naxon\Errors;

use Naxon\Errors\Contracts\Loader;
use Illuminate\Filesystem\Filesystem;

class FileLoader implements Loader
{
    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The default path for the loader.
     *
     * @var string
     */
    protected $path;

    /**
     * All of the registered paths to JSON error files.
     *
     * @var array
     */
    protected $jsonPaths = [];

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $hints = [];

    /**
     * FileLoader constructor.
     *
     * @param Filesystem $files
     * @param string $path
     */
    public function __construct(Filesystem $files, string $path)
    {
        $this->files = $files;
        $this->path = $path;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param null|string $namespace
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function load(string $locale, string $group, ?string $namespace = null): array
    {
        if ($group == '*' && $namespace == '*') {
            return $this->loadJsonPaths($locale);
        }

        if (is_null($namespace) || $namespace == '*') {
            return $this->loadPath($this->path, $locale, $group);
        }

        return $this->loadNamespace($locale, $group, $namespace);
    }

    /**
     * Load a namespaced errors group.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function loadNamespace(string $locale, string $group, string $namespace): array
    {
        if (isset($this->hints[$namespace])) {
            $lines = $this->loadPath($this->hints[$namespace], $locale, $group);

            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    /**
     * Load a local namespaced errors group for overrides.
     *
     * @param array $lines
     * @param string $locale
     * @param string $group
     * @param string $namespace
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadNamespaceOverrides(array $lines, string $locale, string $group, string $namespace): array
    {
        $file = $this->path.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.$namespace.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * @param string $path
     * @param string $locale
     * @param string $group
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadPath(string $path, string $locale, string $group): array
    {
        if ($this->files->exists($full = $path.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php')) {
            return $this->files->getRequire($full);
        }

        return [];
    }

    /**
     * Load a locale from the given JSON file path.
     *
     * @param string $locale
     * @return array
     */
    protected function loadJsonPaths(string $locale): array
    {
        return collect(array_merge($this->jsonPaths, [$this->path]))
            ->reduce(function ($output, $path) use ($locale) {
                if ($this->files->exists($full = $path.DIRECTORY_SEPARATOR.$locale.'.json')) {
                    $decoded = json_decode($this->files->get($full), true);

                    if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Errors file ['.$full.'] contains an invalid JSON structure.');
                    }

                    $output = array_merge($output, $decoded);
                }

                return $output;
            }, []);
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
        $this->hints[$namespace] = $hint;
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath(string $path): void
    {
        $this->jsonPaths[] = $path;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces(): array
    {
        return $this->hints;
    }
}
