<?php


namespace Tests\Process;


use BinDependencies\Dependencies\Utils;
use BinDependencies\Process\Process;
use BinDependencies\Process\ProcessException;
use Tests\TestCase;

class ProcessTestCase extends TestCase
{
    public function testGetPathname()
    {
        $process = new Process('/path/to/process');

        $this->assertEquals('/path/to/process', $process->getPathname());
    }

    public function testGetDescriptors()
    {
        $process = new Process(
            '/path/to/process',
            $descriptors = [
                STDIN,  // STDIN
                STDOUT,  // STDOUT
                STDERR // STDERR
            ]
        );

        $this->assertSame($descriptors, $process->getDescriptors());
    }

    public function testRunSuccess()
    {
        $path = Utils::which('php') . ' --version';

        $process = new Process($path);

        $result = $process->run();

        $this->assertStringContainsString('The PHP Group', $result[0]);
        $this->assertEquals('', $result[1]);
        $this->assertEquals(0, $result[2]);
    }

    public function testRunCreationError()
    {
        $this->expectException(ProcessException::class);

        $process = new Process(
            '/usr/local/bin/sdssd',
            $descriptors = [
                false,
                STDIN,  // STDOUT
            ]
        );

        $process->run();
    }
}