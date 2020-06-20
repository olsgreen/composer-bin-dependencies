<?php
namespace BinDependencies;

use BinDependencies\Configuration\Repository;
use BinDependencies\Configuration\RepositoryInterface;
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

/**
 * A composer plugin to enforce local binary dependency constraints
 * are met on package installation.
 *
 * @package BinDependencies
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Has the root package been validated?
     *
     * @var bool
     */
    protected static $rootPackageValidated = false;

    /**
     * Composer instance.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * IO instsance.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * Plugin repository instance.
     *
     * @var RepositoryInterface
     */
    protected $config;

    /**
     * Dependency validator instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        $this->setDefaultConfigurationRepository();

        $this->validator = new Validator($this->config);
    }

    /**
     * Set the configuration repository instance.
     *
     * @param RepositoryInterface $config
     * @return $this
     */
    public function setConfiguration(RepositoryInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set configuration repository instance to default configuration.
     */
    protected function setDefaultConfigurationRepository(): void
    {
        $defaultPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';

        $this->setConfiguration(new Repository($defaultPath));
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;

        $this->io = $io;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::PRE_PACKAGE_INSTALL => array('validateEventDependencies'),
            PackageEvents::PRE_PACKAGE_UPDATE => array('validateEventDependencies'),
        );
    }

    /**
     * Validate the root packages dependencies.
     *
     * @throws DependencyException
     */
    public function validateRootDependencies()
    {
        if (static::$rootPackageValidated === false) {
            $this->validatePackageDependencies($this->composer->getPackage());

            static::$rootPackageValidated = true;
        }
    }

    /**
     * Validate dependencies attached to an event.
     *
     * @param PackageEvent $event
     * @throws DependencyException
     */
    public function validateEventDependencies(PackageEvent $event)
    {        
        $this->validateRootDependencies();

        $op = $event->getOperation();
        if ($op instanceof InstallOperation || $op instanceof UpdateOperation) {
            $package = $op->getPackage();
            $this->validatePackageDependencies($package);
        }
    }

    /**
     * Validate a packages dependencies.
     *
     * @param PackageInterface $package
     * @throws DependencyException
     */
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