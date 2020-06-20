<?php


namespace BinDependencies\Configuration;


class Repository implements RepositoryInterface
{
    protected $repositoryPath;

    protected $data = [];

    public function __construct(string $path)
    {
        $this->setRepositoryPath($path);
    }

    public function setRepositoryPath(string $path): self
    {
        $this->repositoryPath = $path;

        $this->load();

        return $this;
    }

    public function getRepositoryPath(): string
    {
        return $this->repositoryPath;
    }

    protected function load(): void
    {
        $data = [];

        $files = glob($this->getRepositoryPath() . DIRECTORY_SEPARATOR . '*.json');

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data[pathinfo($file, PATHINFO_FILENAME)] = json_decode($json, true);
        }

        $this->data = $data;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function has(string $key): bool
    {
        return $this->retrieve($key) !== false;
    }

    public function get(string $key, $default = false)
    {
        if ($value = $this->retrieve($key)) {
            return $value;
        }

        return $default;
    }

    protected function retrieve(string $key)
    {
        $segments = explode('.', $key);

        $cursor = $this->data;

        while (count($segments) > 0) {
            $marker = array_shift($segments);

            if (array_key_exists($marker, $cursor)) {
                $cursor = $cursor[$marker];
                continue;
            }

            return false;
        }

        return $cursor;
    }
}