<?php


namespace Tests\Dependencies;


trait BinPathTools
{
    protected $binPath = '';

    public function bootBinPathTrait()
    {
        $paths = explode(PATH_SEPARATOR, $_SERVER['PATH']);

        $this->binPath = array_shift($paths);

        if (!is_dir($this->binPath)) {
            mkdir($this->binPath, 0777, true);
        }
    }
}