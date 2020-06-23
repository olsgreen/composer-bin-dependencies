<?php


namespace Tests\Dependencies;


use BinDependencies\Dependencies\ValidationError;
use Tests\TestCase;

class ValidationErrorTestCase extends TestCase
{
    public function testGetMessage()
    {
        $error = new ValidationError('Test message');

        $this->assertEquals('Test message', $error->getMessage());
    }

    public function testToString()
    {
        $error = new ValidationError('Test message');

        $this->assertEquals('Test message', $error);
    }
}