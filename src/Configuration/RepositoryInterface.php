<?php


namespace BinDependencies\Configuration;


interface RepositoryInterface
{
    /**
     * Get all of the repositories data.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Check to see whether a specified 'dot annotated' key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get the specified 'dot annotated' keys value.
     *
     * @param string $key
     * @param mixed $default
     * @return array|bool|mixed
     */
    public function get(string $key, $default = false);
}