<?php


namespace BinDependencies\Dependencies;


class ValidationErrors
{
    const INSTALLED_VERSION_UNSATISFACTORY = 'The installed version of \'%s\' (%s) does not satisfy the version constraint (%s) required by this package.';

    const NOT_VERSION_CONSTRAINABLE = '\'%s\' is not in the version constrainable list, make a pull request to add it to the plugin.';

    const NOT_EXECUTABLE = '\'%s\' is not executable by the current user.';

    const COULD_NOT_BE_FOUND = '\'%s\' could not be found on the system, please install it and make sure it\'s location is included within the $PATH variable.';
}