<?php


namespace Tests\Process;


use BinDependencies\Process\ProcessFactory;
use BinDependencies\Process\ProcessInterface;
use Tests\TestCase;

class ProcessFactoryTestCase extends TestCase
{
    public function testMakeWithNoDescriptors()
    {
        $factory = new ProcessFactory();

        $process = $factory->make('/path/to/process');

        $this->assertInstanceOf(ProcessInterface::class, $process);
        $this->assertEquals('/path/to/process', $process->getPathname());
    }

    public function testMakeWithDescriptors()
    {
        $factory = new ProcessFactory();

        $process = $factory->make(
            '/path/to/process',
            $descriptors = [
                STDIN,  // STDIN
                STDOUT,  // STDOUT
                STDERR // STDERR
            ]
        );

        $this->assertSame($descriptors, $process->getDescriptors());
    }
}