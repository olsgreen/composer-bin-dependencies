<?php


namespace BinDependencies\Dependencies;


use BinDependencies\Process\ProcessFactory;
use BinDependencies\Process\ProcessFactoryInterface;

/**
 * Resolves a binaries version numbers from pathnames.
 *
 * @package BinDependencies\Dependencies
 */
class VersionResolver implements VersionResolverInterface
{
    /**
     * Process factory instance.
     *
     * @var ProcessFactory|ProcessFactoryInterface
     */
    protected $processFactory;

    /**
     * VersionResolver constructor.
     *
     * @param ProcessFactoryInterface|null $factory
     */
    public function __construct(ProcessFactoryInterface $factory = null)
    {
        if (is_null($factory)) {
            $factory = new ProcessFactory();
        }

        $this->processFactory = $factory;
    }

    /**
     * Resolves a binaries version number from a pathname.
     *
     * @param string $binaryPathname
     * @return string
     * @throws VersionResolverException
     */
    public function resolve(string $binaryPathname): string
    {
        $process = $this->processFactory->make($binaryPathname . ' --version');

        [$stdOut, $stdErr, $exitCode] = $process->run();

        // Merge STDOUT and STDERR as some processes exit with
        // weird codes by correctly output the version.
        $output = array_merge(
            explode(PHP_EOL, $stdOut),
            explode(PHP_EOL, $stdErr)
        );

        // Grab the first non-empty line.
        while (count($output) > 0 && (!isset($firstLine) || empty($firstLine))) {
            $firstLine = trim(array_shift($output));

            if (!empty($firstLine)) {
                break;
            }
        }

        // Try parsing the output for a version number.
        if (isset($firstLine) && !preg_match_all('/(\d+\.\d+(\.\d+)?)/i', $firstLine, $matches)) {
            throw new VersionResolverException(
                sprintf('\'%s\' did not output version information in a recognisable format.', $binaryPathname)
            );
        }

        return $matches[0][0];
    }
}