<?php
namespace BinDependencies\Process;

interface ProcessFactoryInterface
{
    public function make(string $pathname, array $descriptors = null): ProcessInterface;
}