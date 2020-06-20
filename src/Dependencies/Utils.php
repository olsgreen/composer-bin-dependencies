<?php


namespace BinDependencies\Dependencies;


class Utils
{
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