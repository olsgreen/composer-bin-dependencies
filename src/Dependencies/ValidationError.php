<?php


namespace BinDependencies\Dependencies;

/**
 * Generic error entity returned by the validator.
 *
 * @package BinDependencies\Dependencies
 */
class ValidationError
{
    /**
     * The error text.
     *
     * @var string
     */
    protected $error;

    /**
     * ValidationError constructor.
     *
     * @param string $error
     */
    public function __construct(string $error)
    {
        $this->error = $error;
    }

    /**
     * Get the message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->error;
    }

    /**
     * Define the objects string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->error;
    }
}