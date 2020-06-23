<?php


namespace Tests\Dependencies;


use BinDependencies\Dependencies\Utils;
use Tests\TestCase;

class UtilsTestCase extends TestCase
{
    use BinPathTools;

    protected $testExecutable = DIRECTORY_SEPARATOR . 'utils-test-case';

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->bootBinPathTrait();
    }

    public function setUp(): void
    {
        parent::setUp();

        if (!is_dir($this->binPath)) {
            $this->markTestSkipped('A valid bin path could not be found.');
        }

        touch($this->binPath . $this->testExecutable);
    }

    public function testWhichFound()
    {
        $this->assertIsString(Utils::which($this->testExecutable));
    }

    public function testWhichFailed()
    {
        $this->assertFalse(Utils::which('invalid-test-executable'));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->binPath . $this->testExecutable);
    }
}