<?php
namespace BinDependencies\Process;

interface ProcessInterface
{
    public function getPathname(): string;
    public function getDescriptors(): array;
    public function run();
}