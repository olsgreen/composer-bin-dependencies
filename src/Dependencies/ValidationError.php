<?php


namespace BinDependencies\Dependencies;


class ValidationError
{
    protected $error;

    public function __construct(string $error)
    {
        $this->error = $error;
    }

    public function getMessage()
    {
        return $this->error;
    }

    public function __toString()
    {
        return $this->error;
    }
}