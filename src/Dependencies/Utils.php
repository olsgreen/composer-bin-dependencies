<?php


namespace BinDependencies\Dependencies;

/**
 * Utility methods.
 *
 * @package BinDependencies\Dependencies
 */
class Utils
{
    /**
     * Locate the pathname of an user executable.
     *
     * @param string $executable
     * @return bool|string
     */
    public static function which(string $executable)
    {
        $paths = explode(PATH_SEPARATOR, getenv("PATH"));

        foreach ($paths as $path) {
            $pathname = $path . DIRECTORY_SEPARATOR . $executable;
            if (file_exists($pathname)) {
                return $pathname;
            }
        }

        return false;
    }
}