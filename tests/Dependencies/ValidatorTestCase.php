<?php


namespace Tests\Dependencies;


use BinDependencies\Configuration\RepositoryInterface;
use BinDependencies\Dependencies\ValidationError;
use BinDependencies\Dependencies\Validator;
use BinDependencies\Dependencies\VersionResolverInterface;
use Composer\Repository\ConfigurableRepositoryInterface;
use Tests\TestCase;

class ValidatorTestCase extends TestCase
{
    use BinPathTools;

    protected $versionResolver;

    protected $validator;

    protected $config;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->bootBinPathTrait();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->versionResolver = $this->prophet->prophesize(VersionResolverInterface::class);

        $this->config = $this->prophet->prophesize(RepositoryInterface::class);

        $this->validator = new Validator($this->config->reveal(), $this->versionResolver->reveal());
    }

    public function testValidateCouldNotBeFound()
    {
        $result = $this->validator->validate('no-such-binary-here');

        $this->assertInstanceOf(ValidationError::class, $result);
        $this->assertStringContainsString(' could not be found on the system', $result);
    }

    public function testValidateNotExecutable()
    {
        $path = $this->binPath . '/cbd-not-executable';

        touch($path);

        $result = $this->validator->validate('cbd-not-executable');

        unlink($path);

        $this->assertInstanceOf(ValidationError::class, $result);
        $this->assertStringContainsString('is not executable by the current user.', $result);
    }

    public function testValidateNotVersionConstrainable()
    {
        $path = $this->binPath . '/cbd-not-version-constrainable';

        touch($path);
        chmod($path, 0700);

        $result = $this->validator->validate('cbd-not-version-constrainable', '>2.0');

        unlink($path);

        $this->assertInstanceOf(ValidationError::class, $result);
        $this->assertStringContainsString('is not in the version constrainable list', $result);
    }

    public function testValidateInstalledVersionUnsatisfactory()
    {
        $path = $this->binPath . '/php';
        $this->config->get('binaries', [])->willReturn(['php'])->shouldBeCalledTimes(1);
        $this->versionResolver->resolve($path)->willReturn(1000)->shouldBeCalledTimes(1);

        $result = $this->validator->validate('php', '>2000');

        $this->assertInstanceOf(ValidationError::class, $result);
        $this->assertStringContainsString('does not satisfy the version constraint', $result);
    }

    public function testValidatePass()
    {
        $path = $this->binPath . '/validate-pass';

        touch($path);
        chmod($path, 0700);

        $result = $this->validator->validate('validate-pass');

        unlink($path);

        $this->assertTrue($result);
    }

    public function testValidateConstrainedPass()
    {
        $path = $this->binPath . '/php';
        $this->config->get('binaries', [])->willReturn(['php'])->shouldBeCalledTimes(1);
        $this->versionResolver->resolve($path)->willReturn(PHP_VERSION)->shouldBeCalledTimes(1);

        $result = $this->validator->validate('php', '>5.6');

        $this->assertTrue($result);
    }

    public function testValidateListPass()
    {
        $path = $this->binPath . '/validate-pass';
        $phpPath = $this->binPath . '/php';

        touch($path);
        chmod($path, 0700);

        $this->config->get('binaries', [])->willReturn(['php'])->shouldBeCalledTimes(2);

        $result = $this->validator->validateList(['validate-pass', 'php' => '*']);

        unlink($path);

        $this->assertEquals([], $result);
    }

    public function testValidateListFail()
    {
        $path = $this->binPath . '/validate-pass';
        $phpPath = $this->binPath . '/php';

        touch($path);
        chmod($path, 0700);

        $this->config->get('binaries', [])->willReturn(['php'])->shouldBeCalledTimes(2);
        $this->versionResolver->resolve($phpPath)->willReturn(PHP_VERSION)->shouldBeCalledTimes(1);

        $result = $this->validator->validateList(['validate-pass', 'php' => '<=5.6']);

        unlink($path);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ValidationError::class, $result['php']);
        $this->assertStringContainsString('does not satisfy the version constraint', $result['php']);
    }
}