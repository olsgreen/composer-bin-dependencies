Composer Bin(ary) Dependencies
=====================

![Packagist Version](https://img.shields.io/packagist/v/olsgreen/composer-bin-dependencies)
[![GitHub license](https://img.shields.io/github/license/olsgreen/composer-bin-dependencies)](https://github.com/olsgreen/composer-bin-dependencies)
![Tests](https://github.com/olsgreen/composer-bin-dependencies/workflows/Tests/badge.svg)


A composer plugin to check that local binaries / executables are installed and are of the correct version before package install.

Composer Bin Dependencies plugin can warn users of these missing dependencies or prevent installation completely. 

An example would be a package which relies on `git` being available with a version higher than `2.0`. 
By requiring this plugin and adding the constraint shown below to you `package.json', you can prevent installation:

    ...
    "extra": {
        "binary-dependencies": {
            "require": {
                "git": ">=2.0"
            }
        }
    }
    ...


Installation
------------

```
$ composer require olsgreen/composer-bin-dependencies
```


Usage
-----

You can validate dependencies are available using either require or warn.

`require` will throw an exception and prevent installation if the constraints are not met.

`warn` simply prints a warning message to the user if the constraints are not met.

```json
{
    "require": {
        "olsgreen/composer-bin-dependencies": "dev-master"
    },
    "extra": {
        "binary-dependencies": {
            "require": {
                "git": ">=2.0",
                "ssh": "*"
            },
            "warn": {
                "convert": "<=7.0.8",
                "python": "<=3.0"
            }
        }
    }
}
```

#### Disabling The Plugin
Sometimes you may need to disable the plugin, you can achieve this by setting the environment variable `DISABLE_COMPOSER_BIN_DEPS` with a value of `1` before running composer commands.
This can be achieved by running the following command in your terminal:

    export DISABLE_COMPOSER_BIN_DEPS=1

#### Version Constraints
Only explicit binaries support version constraints, this is due to the need to call the binary to obtain its version number. The definitions can be found [binaries.json](https://github.com/olsgreen/composer-bin-dependencies/blob/master/config/binaries.json) configuration file, 
feel free to open a pull request to add more.

#### License
MIT
