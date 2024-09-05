# wc-subscriptions-recalculate
Bulk update existing WooCommerce Subscriptions when the prices of products change via WP-CLI (verbose by default).

There's two premium plugins to do this but given it's literally like 20 lines of code to do this, I thought that was a little silly. Free to use. :)

I chose to do this via WP-CLI because depending on how many subscriptions you might have it didn't seem right to waste resources running this through the WP-Admin UI, and using WP-CLI makes it a breeze to keep a log of each item that's been updated in case something seems to be wrong.

## How to
1. **Take a backup first.**
2. Install either as an [MU-Plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) or via regular means if preferred.
3. SSH into your server (some providers give you a console for this, otherwise do so manually) and navigate to your WP installation directory.
4. Strongly suggest performing a dry run first to confirm output:
    ```bash
    wp wcsr recalculate --dry-run
    ```
5. If everything looks right, run it again for real:
    ```bash
    wp wcsr recalculate
    ```
6. You'll see a message in the terminal telling you upon complete recalculation. 🎉
