<?php
namespace BinDependencies;

use BinDependencies\Process\ProcessFactory;
use BinDependencies\Process\ProcessFactoryInterface;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use Composer\Semver\Semver;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected static $rootPackageValidated = false;

    protected static $versionConstrainableList = [];

    protected $composer;

    protected $io;

    protected $processFactory;

    public function __construct(ProcessFactoryInterface $factory = null)
    {
        if (is_null($factory)) {
            $factory = new ProcessFactory();
        }

        $this->processFactory = $factory;
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;

        $this->io = $io;
    } 

    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::PRE_PACKAGE_INSTALL => array('resolveEventDependencies'),
            PackageEvents::PRE_PACKAGE_UPDATE => array('resolveEventDependencies'),
            //ScriptEvents::POST_INSTALL_CMD => array('resolveRootDependencies'),
            //ScriptEvents::POST_UPDATE_CMD => array('resolveRootDependencies'),
        );
    }

    public function resolveRootDependencies()
    {
         // Check the ROOT package.
        if (static::$rootPackageValidated === false) {
            $this->resolvePackageDependencies($this->composer->getPackage());

            static::$rootPackageValidated = true;
        }
    }

    public function resolveEventDependencies(PackageEvent $event)
    {        
        $this->resolveRootDependencies();

        // Check the package being currently processed.
        $op = $event->getOperation();
        if ($op instanceof InstallOperation || $op instanceof UpdateOperation) {
            $package = $op->getPackage();
            $this->resolvePackageDependencies($package);
        } else {
            var_dump(get_class($op));
        }
    }

    public function resolvePackageDependencies(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (array_key_exists('cli-binaries', $extra)) {
            if (array_key_exists('warn', $extra['cli-binaries'])) {
                $this->processSegment($package->getName(), $extra['cli-binaries']['warn']);
            }

            if (array_key_exists('require', $extra['cli-binaries'])) {
                $this->processSegment($package->getName(), $extra['cli-binaries']['require'], $bail = true);
            }
        }
    }

    protected function processSegment(string $name, array $dependencies, bool $bail = false)
    {
        $warnings = [];

        foreach ($dependencies as $dependency => $constraint) {
            if (is_numeric($dependency)) {
                $dependency = $constraint;
                $constraint = null;
            }

            try {
                $this->validateDependency($dependency, $constraint, $bail);
            } catch (BinaryConstraintException $ex) {
                if (!$bail) {
                    $warnings[] = $ex->getMessage();
                } else {
                    throw $ex;
                }
            }
        }

        if (!$bail && count($warnings) > 0) {
            $this->io->write('<warning>There were problems with binaries required by the package (' . $name . '):</warning>');
            foreach ($warnings as $warning) {
                $this->io->write('<warning> - ' . $warning . '</warning>');
            }
        }
    }

    public function validateDependency(string $dependency, string $constraint = null, bool $bail = false)
    {
        if ($pathname = $this->resolveExecutablePathname($dependency)) {
            if (is_executable($pathname)) {
                if (!is_null($constraint) && $constraint !== '*' && $this->isVersionConstrainable($dependency)) {
                    $installedVersion = $this->getInstalledVersion($pathname);

                    if (!Semver::satisfies($installedVersion, $constraint)) {
                        throw new BinaryConstraintException(sprintf('The installed version of \'%s\' (%s) does not satisfy the version constraint (%s) required by this package.', $dependency, $installedVersion, $constraint));
                    }
                } elseif (!is_null($constraint) && $constraint !== '*') {
                    throw new BinaryConstraintException(sprintf('\'%s\' is not in the version constrainable list, make a pull request to add it to the plugin.', $dependency));
                } 

                return true;
            } else {
                throw new BinaryConstraintException(sprintf('\'%s\' is not executable by the current user.', $pathname));
            }
        }

        throw new BinaryConstraintException(sprintf('\'%s\' could not be found on the system, please install it and make sure it\'s location is included within the $PATH variable.', $dependency));
    }

    protected function getInstalledVersion(string $pathname): string
    {
        $process = $this->processFactory->make($pathname . ' --version');

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
            throw new BinaryConstraintException(sprintf('\'%s\' did not output version information in a reconisable format.', $pathname));
        }

        return $matches[0][0];
    }

    protected function loadVersionableConstrainableList(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'version-constrainable.json';

        $json = file_get_contents($path);

        static::$versionConstrainableList = json_decode($json, true);
    }

    protected function isVersionConstrainable(string $dependency): bool
    {
        if (!static::$versionConstrainableList) {
            $this->loadVersionableConstrainableList();
        }

        return array_key_exists($dependency, static::$versionConstrainableList);
    }

    protected function getVersionConstrainableListEntry(string $dependency)
    {
        if ($this->isVersionConstrainable($dependency)) {
            return static::$versionConstrainableList[$dependency];
        }

        return false;
    }

    protected function resolveExecutablePathname(string $filename)
    {
        $paths = explode(PATH_SEPARATOR, getenv("PATH")); 
        foreach ($paths as $path) { 
            $pathname = $path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($pathname)) {
                return $pathname; 
            } 
        }

        return false;
    }
}