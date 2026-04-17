Contributing
============

As a small-time repo for a very niche command, I don't expect big contributions or anything but am grateful for assistance where appropriate.

## Testing

The package currently uses Behat for testing the `update` command:

~~~
gh repo clone ara303/wc-subscriptions-recalculate # or clone with regular `git`
composer install
composer prepare-tests 
composer behat
~~~

## Documenting

Good documentation is necessary for good developer tools. It's also really boring. To alleviate that somewhat, this package utilises [`wp scaffold`](https://github.com/wp-cli/scaffold-package-command) to generate the usage docs.

Other sections are not automatically generated and can be found in `docs/`.

After changing either, regenerate docs:

~~~
wp package install wp-cli/wp-scaffold-command
wp scaffold package-readme <working_dir> 
# it'll warn README.md exists, press `r` to replace
~~~

## Checking

~~~
composer validate --strict
~~~