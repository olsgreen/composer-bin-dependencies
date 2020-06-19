<?php
namespace BinDependencies\Process;

class Process implements ProcessInterface
{
    protected $pathname;

    protected $descriptors = array(
       0 => array("pipe", "r"),  // STDIN
       1 => array("pipe", "w"),  // STDOUT
       2 => array("pipe", "w") // STDERR
    );

    public function __construct(string $pathname, array $descriptors = null)
    {
        $this->pathname = $pathname;

        if (!is_null($descriptors)) {
            $this->descriptors = $descriptors;
        }
    }

    public function run()
    {
        $process = proc_open($this->pathname, $this->descriptors, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);

            $stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            $stdErr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $exitCode = proc_close($process);

            return [$stdOut, $stdErr, $exitCode];
        }
    }
}