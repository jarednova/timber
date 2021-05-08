---
title: "Managing Timber with Composer"
order: "30"
---

The following guide should help you manage the installed version of Timber. While we work with specific examples for Timber here, what we describe works for most Composer packages.

## Updating Timber

When you want to update to the latest version of Timber, you can run

```bash
composer update
```

This command will also update all other packages to the latest version. If you want to update Timber only, then you can run the following command.

```bash
composer update timber/timber
```

Be aware that depending on what is listed in your **composer.json** file, updating Timber will give you the latest update *within your specified version range*.

**composer.json**

```json
{
  "require": {
    "timber/timber": "^2.0"
  }
}
```

With this example, you would install all Timber versions bigger than `2.0.0`, but smaller than `3.0.0`. Here are some other examples:

- `2.4.0`: install exactly version `2.4.0`
- `^2.4`: bigger or equal to `2.4.0`, but smaller than `3.0.0`
- `^2.4.4`: bigger or equal to `2.4.4`, but smaller than `3.0.0`
- `~2.4.4`: bigger or equal to `2.4.4`, but smaller than `2.5.0`

The Tilde Version Range `~` in the last example would allow you to restrict updating Timber within a *minor* version. You can read more about that under [Version and Constraints](https://getcomposer.org/doc/articles/versions.md).

That’s the beauty of [Semantic Versioning](https://semver.org/) and one the reasons we chose to use Composer to manage Timber and not release it as a WordPress plugin. Because when you have a plugin, you can’t really follow semantic versioning and put developers in control of when they want to do a major update.

To install a specific version with the `composer require` command, you can append the version after a `:`.

```bash
# composer require timber/timber:{version}

composer require timber/timber:2.0.1
```

## Installing non-stable releases

### Installing a pre-release

Pre-releases of Timber are used to test out releases before we make them stable. Pre-releases are suffixed with `-alpha`, `-beta` or `-rc`.

```bash
# Alpha versions.
-alpha1
-alpha2

# Beta versions.
-beta1
-beta2

# Release candidates.
-rc.1
-rc.2
```

### Installing a development version

When you want to install a development version, you can select pre-release or a Git branch:

```bash
# Install the master branch.
composer require timber/timber:dev-master

# Install the beta version of 2.x.
composer require timber/timber:2.x-beta1

# Install the latest work on the 2.x branch.
composer require timber/timber:dev-2.x

# Install a specific commit
composer require timber/timber:dev-master#2cfc7a2eb6c6db743518f8d9d77f2c6259b5d277
```

To be able to install non-stable releases, you might have to update your minimum stability of your installed packages.

```json
{
    "minimum-stability": "dev",
    "prefer-stable": "true"
}
```

This means that you will allow Composer to install development versions of packages if they are specifically requested, but still prefer the stable version (with `prefer-stable`) of other packages, if the version range allows it.

