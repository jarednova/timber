---
title: "Testing"
---

## Setup a testing environment with PHPUnit

Follow the setup steps for [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV). If you’re having trouble I recommend starting 100% fresh (uninstall and delete everything including VirtualBox). For this walk-through we’re going to start from scratch:

### 1. Install VVV

Follow the [setup instructions](https://varyingvagrantvagrants.org/docs/en-US/installation/) — don't forget to install VirtualBox and Vagrant first! For this tutorial I'm going to assume you've installed into `~/vagrant-local/`

### 2. Set up Timber for tests

Navigate into the `www` directory and clone Timber...

```
$ cd ~/vagrant-local/www/
$ git clone git@github.com:timber/timber.git
```

Now install the necessary Composer files...

```
$ cd timber
$ composer install
```

Ok, you should be ready to run tests. This is where things get interesting. You're going to login to your Vagrant virtual box to run the tests...

### 3. Run the tests!

Connect to your Vagrant instance trough SSH:

```
$ vagrant ssh
```

Now wait for it to bring you into the virtual box from the virtual environment...

```
$ cd /srv/www/timber
$ phpunit
```

You should see a bunch of gobbledygook across your screen (the whole process will take about 3 mins.), but we should see that WordPress is testing successfully. Hurrah! For more info, check out the [Handbook on Automated Testing](http://make.wordpress.org/core/handbook/automated-testing/).

## Writing tests

Now we get to the good stuff. You can add tests to the `timber/tests` directory. Any new features should be covered by tests. You can be a hero and help write tests for existing methods and functionality.

## Gotchas!

- You may need to setup authorization between VVV and GitHub. Just follow the prompts to create a token if that interrupts the `composer install`.
- You may have [memory problems with Composer](https://getcomposer.org/doc/articles/troubleshooting.md#proc-open-fork-failed-errors). In case that happens, here’s the script I run:

```
sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
sudo /sbin/mkswap /var/swap.1
sudo /sbin/swapon /var/swap.1
```
