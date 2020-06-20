<?php


namespace BinDependencies\Configuration;


interface RepositoryInterface
{
    public function all(): array;
    public function has(string $key): bool;
    public function get(string $key, $default = false);
}