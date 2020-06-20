<?php


namespace BinDependencies\Dependencies;


use BinDependencies\Process\ProcessFactory;
use BinDependencies\Process\ProcessFactoryInterface;

class VersionResolver
{
    protected $processFactory;

    public function __construct(ProcessFactoryInterface $factory = null)
    {
        if (is_null($factory)) {
            $factory = new ProcessFactory();
        }

        $this->processFactory = $factory;
    }

    public function resolve(string $binaryPathname): string
    {
        $process = $this->processFactory->make($binaryPathname . ' --version');

        [$stdOut, $stdErr, $exitCode] = $process->run();

        $output = array_merge(
            explode(PHP_EOL, $stdOut),
            explode(PHP_EOL, $stdErr)
        );

        while (count($output) > 0 && (!isset($firstLine) || empty($firstLine))) {
            $firstLine = trim(array_shift($output));

            if (!empty($firstLine)) {
                break;
            }
        }

        if (!preg_match_all('/(\d+\.\d+(\.\d+)?)/i', $firstLine, $matches)) {
            throw new VersionResolverException(
                sprintf('\'%s\' did not output version information in a recognisable format.', $binaryPathname)
            );
        }

        return $matches[0][0];
    }
}