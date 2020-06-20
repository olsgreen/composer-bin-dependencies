<?php


namespace BinDependencies\Configuration;

/**
 * Simple read-only configuration store that is hydrated from JSON files.
 *
 * @package BinDependencies\Configuration
 */

class Repository implements RepositoryInterface
{
    /**
     * Path to the JSON configuration files.
     *
     * @var string
     */
    protected $repositoryPath;

    /**
     * Repository store.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Repository constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setRepositoryPath($path);
    }

    /**
     * Set the location of the repository source
     * files and load them.
     *
     * @param string $path
     * @return $this
     */
    public function setRepositoryPath(string $path): self
    {
        $this->repositoryPath = $path;

        $this->load();

        return $this;
    }

    /**
     * Get the repository source path.
     *
     * @return string
     */
    public function getRepositoryPath(): string
    {
        return $this->repositoryPath;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->data;
    }


    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->retrieve($key) !== false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = false)
    {
        if ($value = $this->retrieve($key)) {
            return $value;
        }

        return $default;
    }

    /**
     * Load the repository data from source files.
     */
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

    /**
     * Retrieve a key from the store.
     *
     * @param string $key
     * @return array|bool|mixed
     */
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