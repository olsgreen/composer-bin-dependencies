<?php


namespace BinDependencies\Dependencies;


use BinDependencies\Configuration\Repository;
use Composer\Semver\Semver;

class Validator
{
    protected $versionResolver;

    protected $config;

    public function __construct(Repository $config = null, VersionResolver $versionResolver = null)
    {
        if (!isset($versionResolver)) {
            $versionResolver = new VersionResolver();
        }

        $this->versionResolver = $versionResolver;

        if (!isset($config)) {
            $config = new Repository();
        }

        $this->config = $config;
    }

    public function validateList(array $dependencies): array
    {
        $errors = [];

        foreach ($dependencies as $dependency => $constraint) {
            if (is_numeric($dependency)) {
                $dependency = $constraint;
                $constraint = null;
            }

            if ($error = $this->validate($dependency, $constraint)) {
                $errors[$dependency] = $error;
            }
        }

        return $errors;
    }

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
                elseif ($this->isValidConstraint($constraint)) {
                    return new ValidationError(sprintf(ValidationErrors::NOT_VERSION_CONSTRAINABLE, $dependency));
                }

                return true;
            } else {
                return new ValidationError(sprintf(ValidationErrors::NOT_EXECUTABLE, $executablePathname));
            }
        }

        return new ValidationError(sprintf(ValidationErrors::COULD_NOT_BE_FOUND, $dependency));
    }

    protected function isValidConstraint(string $constraint)
    {
        return !is_null($constraint) && $constraint !== '*';
    }

    protected function isVersionConstrainable(string $dependency, string $constraint): bool
    {
        $versionConstrainableBinaries = $this->config->get('binaries', []);

        return $this->isValidConstraint($constraint) &&
            in_array($dependency, $versionConstrainableBinaries);
    }
}