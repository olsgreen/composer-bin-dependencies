<?php


namespace BinDependencies\Dependencies;


use BinDependencies\Configuration\RepositoryInterface;
use Composer\Semver\Semver;

/**
 * Validates binary dependencies exist, are executable and optionally
 * match specific version constraints.
 *
 * @package BinDependencies\Dependencies
 */
class Validator
{
    /**
     * The version resolver instance.
     *
     * @var VersionResolver
     */
    protected $versionResolver;

    /**
     * The configuration repository.
     *
     * @var RepositoryInterface
     */
    protected $config;

    /**
     * Validator constructor.
     *
     * @param RepositoryInterface|null $config
     * @param VersionResolverInterface|null $versionResolver
     */
    public function __construct(RepositoryInterface $config, VersionResolverInterface $versionResolver = null)
    {
        $this->versionResolver = $versionResolver ?: new VersionResolver();

        $this->config = $config ;
    }

    /**
     * Validate that a list of dependencies are installed
     * locally and meet the required version constraints.
     *
     * e.g. ['git' => '>=0.1.3', 'ssh']
     *
     * @param array $dependencies
     * @return array
     */
    public function validateList(array $dependencies): array
    {
        $errors = [];

        foreach ($dependencies as $dependency => $constraint) {
            if (is_numeric($dependency)) {
                $dependency = $constraint;
                $constraint = null;
            }

            $response = $this->validate($dependency, $constraint);

            if ($response instanceof ValidationError) {
                $errors[$dependency] = $response;
            }
        }

        return $errors;
    }

    /**
     * Validate a single dependency and version constraint.
     *
     * @param string $dependency
     * @param string|null $constraint
     * @return ValidationError|bool
     * @throws VersionResolverException
     */
    public function validate(string $dependency, string $constraint = null)
    {
        // First we check for the dependency within all locations within the system $PATH,
        // otherwise we return a 'not found' validation error.
        if ($executablePathname = Utils::which($dependency)) {

            // Next, we check the binary found is executable by the current user, otherwise we
            // return a 'not executable' error.
            if (is_executable($executablePathname)) {

                // For security, we require each binary that should be version constrainable to be explicitly
                // defined within config/binaries.json file, this is due to the need to call the binary.
                if ($this->isVersionConstrainable($dependency, $constraint)) {
                    $installedVersion = $this->versionResolver->resolve($executablePathname);

                    if (!Semver::satisfies($installedVersion, $constraint)) {
                        return new ValidationError(
                            sprintf(
                                ValidationErrors::INSTALLED_VERSION_UNSATISFACTORY,
                                $dependency, $installedVersion, $constraint
                            )
                        );
                    }
                }
                // If we are here and the constraint is valid, the dependency is not defined within the
                // config/binaries.json so we throw the a 'no constrainable' error.
                elseif ($this->isActionableConstraint($constraint)) {
                    return new ValidationError(sprintf(ValidationErrors::NOT_VERSION_CONSTRAINABLE, $dependency));
                }

                return true;
            } else {
                return new ValidationError(sprintf(ValidationErrors::NOT_EXECUTABLE, $executablePathname));
            }
        }

        return new ValidationError(sprintf(ValidationErrors::COULD_NOT_BE_FOUND, $dependency));
    }

    /**
     * Checks the supplied constraint is actionable and not a wildcard or empty.
     *
     * @param string $constraint
     * @return bool
     */
    protected function isActionableConstraint(string $constraint)
    {
        return !empty($constraint) && !is_null($constraint) && $constraint !== '*';
    }

    /**
     * Checks the dependency/binary name is defined within the plugins configuration
     * list so that it's binary can be called for a version. We only allow names
     * explicitly defined to be called for security.
     *
     * @param string $dependency
     * @param string $constraint
     * @return bool
     */
    protected function isVersionConstrainable(string $dependency, string $constraint): bool
    {
        $versionConstrainableBinaries = $this->config->get('binaries', []);

        return $this->isActionableConstraint($constraint) &&
            in_array($dependency, $versionConstrainableBinaries);
    }
}