<?php
namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $prophet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new \Prophecy\Prophet;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }
}