<?php


namespace BinDependencies\Dependencies;

/**
 * Error strings used by the validator.
 *
 * @package BinDependencies\Dependencies
 */
class ValidationErrors
{
    /**
     * Returned when the version of a binary does not meet the defined
     * constraint. Note that versions are compared semantically.
     *
     * @var string
     */
    const INSTALLED_VERSION_UNSATISFACTORY = 'The installed version of \'%s\' (%s) does not satisfy the version constraint (%s) required by this package.';

    /**
     * For security the plugin will only try to compare versions of binaries explicitly defined within
     * it's configuration file.
     *
     * This error is returned when the constraint defined within the packages composer.json includes a version
     * but the binary is not defined within the aforementioned list. Feel free to open a pull request for
     * your desired binary.
     *
     * @see https://github.com/olsgreen/composer-bin-dependencies/blob/master/config/binaries.json
     *
     * @var string
     */
    const NOT_VERSION_CONSTRAINABLE = '\'%s\' is not in the version constrainable list, make a pull request to add it to the plugin. (https://github.com/olsgreen/composer-bin-dependencies/blob/master/config/binaries.json)';

    /**
     * Returned when the binary exists but is not executable by the current user.
     *
     * @var string
     */
    const NOT_EXECUTABLE = '\'%s\' is not executable by the current user.';

    /**
     * Returned when the binary could not be found in any of the paths defined in the $PATHS variable.
     */
    const COULD_NOT_BE_FOUND = '\'%s\' could not be found on the system, please install it and make sure it\'s location is included within the $PATH variable.';
}