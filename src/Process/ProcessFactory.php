<?php
namespace BinDependencies\Process;

class ProcessFactory implements ProcessFactoryInterface
{
    public function make(string $pathname, array $descriptors = null): ProcessInterface
    {
        return new Process($pathname, $descriptors);
    }
}