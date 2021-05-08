---
title: "Installation"
order: "10"
---

You can install Timber through [Composer](https://getcomposer.org/download/). If you’re not familiar with Composer yet, read [our short introduction](https://timber.github.io/docs/v2/installation/intro-to-composer/) and then come back here.

In earlier versions of Timber, you could install Timber as a WordPress plugin. We dropped support for that, because Timber is not really a plugin. Plugins are supposed to enhance functionality. But when you're developing with Timber, it’s really a dependency — and Composer is the premier PHP dependency manager.

## Install Timber into an existing project

To install Timber, you can use the following command:

```bash
composer require timber/timber
```

Now in which folder do you run that command?

You can choose yourself where in your project you want to include Timber.

- Some developers prefer to have Timber installed as a theme dependency, so they would run this command from the **theme root** (ex: **/wp-content/themes/my-theme/**).
- Others want to use Timber as a WordPress dependency, which means you would run the command above from the **WordPress root** (ex: **/var/www/**).

If your theme or project is not already set up to pull in Composer’s autoload file, you will need to add the following line at the top of your **functions.php** file:

```php
<?php

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Timber.
new Timber\Timber();
```

## Use the Starter Theme

If you want to use Timber’s [Starter Theme](https://github.com/timber/starter-theme), you can use `composer create-project`. Run the following command from the **wp-content/themes** folder of your WordPress installation.

```bash
composer create-project upstatement/timber-starter-theme --no-dev
```

This command will install the Starter Theme with Timber included as a Composer dependency.

### 1. Navigate to your WordPress themes directory

Like where twentyeighteen and twentynineteen live, the Timber Starter Theme will live at the same level.

```
/wp-content/themes/twentyeighteen
                  /twentynineteen
                  /timber-starter-theme
```

You should now have:

```
/wp-content/themes/timber-starter-theme
```

You should probably **rename** this to something better.

### 2. Select your theme in WordPress

Navigate to the Manage Themes page in your WordPress admin (**Appearance** → **Themes**). Select the **timber-starter-theme** theme from the step above (or whatever you renamed it to).

### 3. Let’s write our theme!

Dive right in or use the [Getting Started Guide](/docs/v2/getting-started/introduction/) to learn more about how to develop themes with Timber.
