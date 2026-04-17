#### Prerequisites

- WP-CLI >=2.13 

_Note:_ WP <7.0 does not have WP-CLI 2.13. Install manually: `wp cli update --nightly`.

### As package

As of 1.0.0 release, install as a WordPress package:

~~~
wp package install ara303/wc-subscriptions-recalculate
~~~

### As MU-Plugin (unsupported)

If you really want, take the contents of `src/Command.php` and put it into your own MU-Plugin.