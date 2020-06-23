<?php


namespace Tests;


use BinDependencies\Configuration\Repository;

class ConfigurationTestCase extends TestCase
{
    protected $repository;

    public function setUp(): void
    {
        parent::setup();

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'data';

        $this->repository =  new Repository($path);
    }

    public function testSetGetRepositoryPath()
    {

        $this->repository->setRepositoryPath(sys_get_temp_dir());

        $this->assertEquals(sys_get_temp_dir(), $this->repository->getRepositoryPath());
    }

    public function testAll()
    {
        $expected = [
            'foo' => ['bar' => 'baz'],
            'bar' => ['baz' => 'qux']
        ];

        $this->assertEquals($expected, $this->repository->all());
    }

    public function testHasReturnsTrue()
    {
        $this->assertTrue($this->repository->has('foo.bar'));
    }

    public function testHAsReturnFalse()
    {
        $this->assertFalse($this->repository->has('bar.foo'));
    }

    public function testGet()
    {
        $this->assertEquals('baz', $this->repository->get('foo.bar'));
    }

    public function testGetReturnsDefault()
    {
        $this->assertFalse($this->repository->get('bar.foo'));
    }

    public function testGetReturnsCustomDefault()
    {
        $this->assertEquals('default', $this->repository->get('bar.foo', 'default'));
    }
}