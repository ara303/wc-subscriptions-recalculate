#### Prerequisites

- WP-CLI 2.13 or newer (`wp cli update --nightly` if you're using WP older than 7.0, which bundles WP-CLI 2.13)

### As package

As of release version 1.0.0 (stable), this is now installable as a WordPress package.

1. In WP-CLI, run: `wp package install ara303/wc-subscriptions-recalculate`
3. See [Using](#using)

### Use as MU-Plugin

If you really want, take the contents of `src/Command.php` and put it into your own MU-Plugin (warning: unsupported).