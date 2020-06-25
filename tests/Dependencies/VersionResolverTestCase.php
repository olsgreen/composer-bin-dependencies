<?php


namespace Tests\Dependencies;


use BinDependencies\Dependencies\VersionResolver;
use BinDependencies\Dependencies\VersionResolverException;
use BinDependencies\Process\ProcessFactoryInterface;
use BinDependencies\Process\ProcessInterface;
use Tests\TestCase;

class VersionResolverTestCase extends TestCase
{
    protected $processFactory;

    protected $process;

    protected $resolver;

    public function setUp(): void
    {
        parent::setUp();

        $this->processFactory = $this->prophet->prophesize(ProcessFactoryInterface::class);

        $this->process = $this->prophet->prophesize(ProcessInterface::class);

        $this->resolver = new VersionResolver($this->processFactory->reveal());
    }

    public function testResolveSuccessStdOut()
    {
        $this->process->run()->willReturn([
            'Test Command 1.0.0',
            '',
            0
        ])->shouldBeCalledTimes(1);

        $this->processFactory->make('test --version')->willReturn(
            $this->process->reveal()
        )->shouldBeCalledTimes(1);

        $version = $this->resolver->resolve('test');

        $this->assertEquals('1.0.0', $version);
    }

    public function testResolveSuccessStdErr()
    {
        $this->process->run()->willReturn([
            '',
            'Test Command 1.0.0',
            1
        ])->shouldBeCalledTimes(1);

        $this->processFactory->make('test --version')->willReturn(
            $this->process->reveal()
        )->shouldBeCalledTimes(1);

        $version = $this->resolver->resolve('test');

        $this->assertEquals('1.0.0', $version);
    }

    public function testResolveNoProcessOutput()
    {
        $this->expectException(VersionResolverException::class);

        $this->process->run()->willReturn([
            '',
            '',
            0
        ])->shouldBeCalledTimes(1);

        $this->processFactory->make('test --version')->willReturn(
            $this->process->reveal()
        )->shouldBeCalledTimes(1);

        $this->resolver->resolve('test');
    }

    public function testResolveBadProcessOutput()
    {
        $this->expectException(VersionResolverException::class);

        $this->process->run()->willReturn([
            'No version included in this message',
            '',
            0
        ])->shouldBeCalledTimes(1);

        $this->processFactory->make('test --version')->willReturn(
            $this->process->reveal()
        )->shouldBeCalledTimes(1);

        $this->resolver->resolve('test');
    }
}