<?php
namespace BinDependencies;

use BinDependencies\Configuration\Repository;
use BinDependencies\Configuration\RepositoryInterface;
use BinDependencies\Dependencies\DependencyException;
use BinDependencies\Dependencies\Validator;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected static $rootPackageValidated = false;

    protected $composer;

    protected $io;

    protected $config;

    protected $validator;

    public function __construct()
    {
        $this->setDefaultConfigurationRepository();

        $this->validator = new Validator($this->config);
    }

    public function setConfiguration(RepositoryInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    protected function setDefaultConfigurationRepository(): void
    {
        $defaultPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';

        $this->setConfiguration(new Repository($defaultPath));
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;

        $this->io = $io;
    } 

    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::PRE_PACKAGE_INSTALL => array('validateEventDependencies'),
            PackageEvents::PRE_PACKAGE_UPDATE => array('validateEventDependencies'),
            //ScriptEvents::POST_INSTALL_CMD => array('resolveRootDependencies'),
            //ScriptEvents::POST_UPDATE_CMD => array('resolveRootDependencies'),
        );
    }

    public function validateRootDependencies()
    {
        if (static::$rootPackageValidated === false) {
            $this->validatePackageDependencies($this->composer->getPackage());

            static::$rootPackageValidated = true;
        }
    }

    public function validateEventDependencies(PackageEvent $event)
    {        
        $this->validateRootDependencies();

        $op = $event->getOperation();
        if ($op instanceof InstallOperation || $op instanceof UpdateOperation) {
            $package = $op->getPackage();
            $this->validatePackageDependencies($package);
        }
    }

    public function validatePackageDependencies(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (array_key_exists('binary-dependencies', $extra)) {

            // Process the packages dependency warning list, this simple outputs warnings to the console.
            if (array_key_exists('warn', $extra['binary-dependencies'])) {
                $errors = $this->validator->validateList($extra['binary-dependencies']['warn']);

                if (count($errors) > 0) {
                    $this->io->write(
                        '<warning>There were problems with binaries required by the package (' . $package->getName() . ')</warning>'
                    );

                    foreach ($errors as $error) {
                        $this->io->write('<warning> - ' . $error . '</warning>');
                    }
                }
            }

            // Process the packages dependency requirement list, this will throw an exception
            // preventing installation of the package.
            if (array_key_exists('require', $extra['binary-dependencies'])) {
                $errors = $this->validator->validateList($extra['binary-dependencies']['require']);

                if (count($errors) > 0) {
                    $message = 'Problems with binaries required by the package (' . $package->getName() . ') prevented installation:';

                    foreach ($errors as $error) {
                        $message .= PHP_EOL . ' - ' . $error;
                    }

                    throw new DependencyException($message);
                }
            }
        }
    }
}