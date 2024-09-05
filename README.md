# wc-subscriptions-recalculate
Bulk update existing WooCommerce Subscriptions when the prices of products change, via a WP-CLI command (`wcs-recalculate`). Verbose by default.

There's two premium plugins to do this but given it's literally like 20 lines of code to do this, I thought that was a little silly. Free to use. :)

I chose to do this via WP-CLI because depending on how many subscriptions you might have it didn't seem right to waste resources running this through the WP-Admin UI, and using WP-CLI makes it a breeze to keep a log of each item that's been updated in case something seems to be wrong.

## How to
1. Suggested installation as an [MU-Plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) but you can activate this as a "normal" plugin if you prefer.
2. SSH into your server (some providers give you a console for this, but for others you might need to set it up manually), navigate to your WP installation directory.
3. Run `wcs-recalculate` and wait while the script iterates over all active subscriptions.
4. A success message will be shown upon complete recalculation. 🎉
