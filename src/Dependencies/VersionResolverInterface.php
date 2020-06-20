<?php


namespace BinDependencies\Dependencies;


interface VersionResolverInterface
{
    /**
     * Resolves a binaries version number from a pathname.
     *
     * @param string $binaryPathname
     * @return string
     */
    public function resolve(string $binaryPathname): string;
}