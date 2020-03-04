<p align="center">
<img src="https://app.codeship.com/projects/673bef30-407c-0138-2ef9-16df26dc09b2/status?branch=master" alt="Build Status">
<img src='https://coveralls.io/repos/github/ChrGriffin/laravel-collection-package-generator/badge.svg?branch=master' alt='Coverage Status' />
</p>

# Collection Macro Package Generator

## Usage

Install globally via composer:

```shell script
composer global require chrgriffin/collection-macro-package-generator
```

You can now quickly scaffold a Laravel Collection macro package by running:

```shell script
collection new <macro command>
```

This will create a directory called `collection-macro-<macro command>` containing a scaffolded composer package of the same name.

## Notes

This package was primarily written for myself ([ChrGriffin](https://github.com/ChrGriffin)). Feel free to use it yourself or fork it, but I don't have any intention of maintaining it for use cases beyond my own.

This generator used the [Laravel Installer](https://github.com/laravel/installer) as a base that was subsequently edited.
