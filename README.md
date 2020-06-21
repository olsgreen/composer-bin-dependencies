![License]](https://github.com/wikimedia/composer-merge-plugin/blob/master/LICENSE)
![Tests](https://github.com/olsgreen/composer-bin-dependencies/workflows/Tests/badge.svg)

Composer Bin(ary) Dependencies
=====================

A composer plugin to enforce local binary dependency constraints are met on package installation.

Composer Bin Dependencies plugin is intended to prevent or warn users of missing binary / executable 
dependencies when installing a project or a library. 

An example would be a package which relies on `git` being available and ar or newer than version `2.0`. 
By requiring this plugin and adding the constraint shown below to you `package.json', you can prevent installation (or only warn of it's absence):

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

#### Version Constraints
Only explicit binaries support version constraints, this is due to the need to call the binary to obtain its version number. The definitions can be found [binaries.json](http://) configuration file, 
feel free to open a pull request to add more.

#### License
MIT
