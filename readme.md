# Venne:Packages [![Build Status](https://secure.travis-ci.org/Venne/packages.png)](http://travis-ci.org/Venne/packages)

Simple solution for managing packages in Nette framework.

What problems can it solve?

- Registering extensions to `Nette\Compiler`
- Creating default config structure in `config.neon`
- Publishing directory with assets into `%wwwDir%`
- Resolving paths to packages by simple syntax


## Installation

**Install package manager**

```sh
composer require venne/packages:@dev           // as a dependency
# composer create-project venne/packages:@dev  // aside from the project
```

**add hooks to root `composer.json` file**

```json
{
	"scripts": {
		"post-install-cmd": [
			"vendor/bin/package-manager sync --composer"
		],
		"post-update-cmd": [
			"vendor/bin/package-manager sync --composer"
		]
	}
}
```

**Use it by composer commands**

```sh
composer require kdyby/doctrine
```


## Package definition

If you have already created `composer.json` file, you're done ;)

**Metadata**

Metadata allow you to define advanced features:

- `relativePublicPath`: directory with asset files.
- `configuration`: array will be copied to `config.neon` file.
- `installers`: array of classes implements `Venne\Packages\IInstaller`.

There are three ways how to define metadata:

#### 1) In `composer.json`:

```json
{
	"extra": {
		"venne": {
			"relativePublicPath": "/Resources/public",
			"configuration": {
				"extensions": {
					"translation": "Kdyby\\Translation\\DI\\TranslationExtension"
				}
			},
			"installers": [
				"Namespace\MyInstaller"
			]
		}
	}
}
```

#### 2) In `.venne.php` file:

Implement interface `Venne\Packages\IPackage`

```php
<?php

namespace MyProject;

class Package extends \Venne\Packages\Package {}
```

#### 3) In `venne/packages-metadata` repository:

Fork and edit [https://github.com/Venne/packages-metadata](https://github.com/Venne/packages-metadata)


## Extra functions

Package manager can provide some services which you can use it in your application. Make sure that you have installed package manager as dependency:

```sh
composer require venne/packages:@dev
```

Now you are using it as regular package. Not only independent package manager.

### Service `Venne\Packages\PathResolver`

Service for resolving paths to packages by `@` syntax. Use `.` as separator between project and package name. For example: `@kdyby.doctrine`.

```php
$pathResolver = $container->getByType('Venne\Packages\PathResolver');
echo $pathResolver->expandPath('@my.package/foo/bar');        // /path_to_my_package/foo/bar
echo $pathResolver->expandResource('@my.package/foo/bar');    // {$resourcesDir}/my/package/foo/bar
```

### Macros

New macros and new possibilities in classic macros:

```php
{path @my.package/file}                   // browser_path_to_my.package/file
{extends @my.package/@layout.latte}       // extends from other package
{includeblock @my.package/@layout.latte}  // include blocks from template
```


## Commands

```sh
vendor/bin/package-manager list                # List packages
vendor/bin/package-manager sync                # Synchronize all packages with filesystem
vendor/bin/package-manager install <name>      # Install package
vendor/bin/package-manager uninstall <name>    # Uninstall package
```

### Example of package installation

```sh
composer require foo/bar:2.0.x [--prefer-dist] # Download package
vendor/bin/package-manager update              # Update local database of packages
vendor/bin/package-manager install foo.bar     # Install package
```

