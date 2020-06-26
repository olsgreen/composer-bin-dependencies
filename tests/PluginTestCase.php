<?php
namespace Tests;

use BinDependencies\Configuration\RepositoryInterface;
use BinDependencies\Dependencies\Validator;
use BinDependencies\Plugin;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;

class PluginTestCase extends TestCase
{
    protected $plugin;

    protected $validator;

    protected $composer;

    protected $io;

    protected $package;

    public function setUp(): void
    {
        parent::setUp();

        $this->composer = $this->prophet->prophesize(Composer::class);

        $this->io = $this->prophet->prophesize(IOInterface::class);

        $this->package = $this->prophet->prophesize(PackageInterface::class);

        $this->validator = $this->prophet->prophesize(Validator::class);

        $this->bootPlugin();
    }

    protected function bootPlugin()
    {
        $this->plugin = new Plugin();
        $this->plugin->setValidator($this->validator->reveal());
        $this->plugin->activate($this->composer->reveal(), $this->io->reveal());
    }

    public function testDefaultConfiguration()
    {
        $path = dirname(__DIR__) . '/config/binaries.json';

        $binaries = [
            'binaries' => json_decode(file_get_contents($path),true)
        ];

        $this->assertEquals($binaries, $this->plugin->getConfiguration()->all());
    }

    public function testGetSetConfiguration()
    {
        $config = $this->prophet->prophesize(RepositoryInterface::class);

        $this->plugin->setConfiguration($config->reveal());

        $this->assertSame($config->reveal(), $this->plugin->getConfiguration());
    }

    public function testGetSetValidator()
    {
        $validator = $this->prophet->prophesize(Validator::class);

        $this->plugin->setValidator($validator->reveal());

        $this->assertSame($validator->reveal(), $this->plugin->getValidator());
    }

    public function testGetSubscribedEvents()
    {
        $events = Plugin::getSubscribedEvents();

        $this->assertCount(2, $events);
        $this->assertEquals('validateEventDependencies', array_pop($events));
        $this->assertEquals('validateEventDependencies', array_pop($events));
    }

    public function testValidatePackageDependenciesNoPackagePluginConfigFound()
    {
        $this->package->getExtra()->willReturn([])->shouldBeCalledTimes(1);

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesPackagePluginConfigFound()
    {
        $this->package->getExtra()->willReturn(['binary-dependencies' => []])->shouldBeCalledTimes(1);

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testEnabledByDefault()
    {
        $this->assertTrue($this->plugin->isEnabled());
    }

    public function testDisableByEnv()
    {
        putenv('DISABLE_COMPOSER_BIN_DEPS=1');

        $this->bootPlugin();

        $this->assertFalse($this->plugin->isEnabled());

        putenv('DISABLE_COMPOSER_BIN_DEPS=0');
    }

    public function testDisable()
    {
        $this->plugin->disable();

        $this->assertFalse($this->plugin->isEnabled());
    }

    public function testEnable()
    {
        $this->plugin->disable();
        $this->plugin->enable();

        $this->assertTrue($this->plugin->isEnabled());
    }

    public function testValidatePackageDependenciesWarningsPassed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'warn' => $deps = [
                    'php' => '>=7.0'
                ]
            ]
        ]);

        $this->validator->validateList($deps)->willReturn([])->shouldBeCalledTimes(1);

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesSingleWarningFailed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'warn' => $deps = [
                    'php' => '>=7.0'
                ]
            ]
        ]);
        $this->package->getName()->willReturn('Package Name')->shouldBeCalledTimes(1);

        $this->validator->validateList($deps)->willReturn([
            'php' => 'Foo blah blah blah...'
        ])->shouldBeCalledTimes(1);

        $this->io->write('<warning>There were problems with binaries required by the package (Package Name)</warning>')->shouldBeCalled();
        $this->io->write('<warning> - Foo blah blah blah...</warning>')->shouldBeCalled();

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesMultipleWarningFailed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'warn' => $deps = [
                    'php' => '>=7.0',
                    'ruby' => '>=2.32'
                ]
            ]
        ]);

        $this->package->getName()->willReturn('Package Name')->shouldBeCalledTimes(1);

        $this->validator->validateList($deps)->willReturn([
            'php' => 'Foo blah blah blah...',
            'ruby' => 'Ruby blah blah blah...'
        ])->shouldBeCalledTimes(1);


        $this->io->write('<warning>There were problems with binaries required by the package (Package Name)</warning>')->shouldBeCalled();
        $this->io->write('<warning> - Foo blah blah blah...</warning>')->shouldBeCalled();
        $this->io->write('<warning> - Ruby blah blah blah...</warning>')->shouldBeCalled();

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesRequirementsPassed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'require' => $deps = [
                    'php' => '>=7.0'
                ]
            ]
        ]);

        $this->validator->validateList($deps)->willReturn([])->shouldBeCalledTimes(1);

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesSingleRequirementFailed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'require' => $deps = [
                    'php' => '>=7.0'
                ]
            ]
        ]);

        $this->package->getName()->willReturn('Package Name')->shouldBeCalledTimes(1);

        $this->validator->validateList($deps)->willReturn([
            'php' => 'PHP blah blah blah...'
        ])->shouldBeCalledTimes(1);

        $this->expectExceptionMessage('Problems with binaries required by the package (Package Name) prevented installation:' . PHP_EOL . ' - PHP blah blah blah...');

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidatePackageDependenciesMultipleRequirementFailed()
    {
        $this->package->getExtra()->willReturn([
            'binary-dependencies' => [
                'require' => $deps = [
                    'php' => '>=7.0',
                    'ruby' => '^2.0'
                ]
            ]
        ]);

        $this->package->getName()->willReturn('Package Name')->shouldBeCalledTimes(1);

        $this->validator->validateList($deps)->willReturn([
            'php' => 'PHP blah blah blah...',
            'ruby' => 'Ruby blah blah blah...'
        ])->shouldBeCalledTimes(1);

        $this->expectExceptionMessage('Problems with binaries required by the package (Package Name) prevented installation:' . PHP_EOL . ' - PHP blah blah blah...' . PHP_EOL . ' - Ruby blah blah blah...');

        $this->plugin->validatePackageDependencies($this->package->reveal());
    }

    public function testValidateRootDependencies()
    {
        $root = $this->prophet->prophesize(RootPackage::class);
        $root->getExtra()->willReturn([]);

        $this->composer->getPackage()->willReturn($root->reveal())->shouldBeCalledTimes(1);

        // We call validateRootDependencies() twice to ensure that the
        // validation will only occur once.
        $this->plugin->validateRootDependencies();
        $this->plugin->validateRootDependencies();
    }

    protected function setRootPackageValidationExpectations()
    {
        if (!$this->plugin->hasRootPackageBeenValidated()) {
            $root = $this->prophet->prophesize(RootPackage::class);
            $root->getExtra()->willReturn([]);

            $this->composer->getPackage()->willReturn($root->reveal())->shouldBeCalledTimes(1);
        }
    }

    protected function validateEventDependencies($operation)
    {
        $event = $this->prophet->prophesize(PackageEvent::class);

        $this->package->getExtra()->willReturn([]);

        $event->getOperation()->willReturn($operation->reveal())->shouldBeCalledTimes(1);

        $this->plugin->validateEventDependencies($event->reveal());
    }

    public function testValidateEventDependenciesDisabled()
    {
        $this->plugin->disable();

        $event = $this->prophet->prophesize(PackageEvent::class);

        $this->package->getExtra()->willReturn([]);

        $this->plugin->validateEventDependencies($event->reveal());
    }

    public function testValidateEventDependenciesInstallOperation()
    {
        $this->setRootPackageValidationExpectations();

        $operation = $this->prophet->prophesize(InstallOperation::class);

        $operation->getPackage()->willReturn($this->package->reveal())->shouldBeCalledTimes(1);

        $this->validateEventDependencies($operation);
    }

    public function testValidateEventDependenciesUpdateOperation()
    {
        $this->setRootPackageValidationExpectations();

        $operation = $this->prophet->prophesize(UpdateOperation::class);

        $operation->getTargetPackage()->willReturn($this->package->reveal())->shouldBeCalledTimes(1);

        $this->validateEventDependencies($operation);

    }

    public function testValidateEventDependenciesInvalidEvent()
    {
        $this->setRootPackageValidationExpectations();

        $this->validateEventDependencies(
            $this->prophet->prophesize(UninstallOperation::class)
        );

    }
}