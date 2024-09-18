# wc-subscriptions-recalculate
> [!IMPORTANT]
 VAT/tax calculation is only suitable for my usecase (enter prices without and only add it at checkout, relying on built-in tax calculation methods). Depending on what regions and tax laws you have to comply with this may or may not apply to you.

Bulk update existing WooCommerce Subscriptions when the prices of products change via WP-CLI (verbose by default).

I do this through WP-CLI because depending on how many subscriptions you might have it didn't seem right to waste resources running this through the WP-Admin UI, and using WP-CLI makes it a breeze to keep a log of each item that's been updated in case something seems to be wrong.

## Installation
2. Install either as an [MU-Plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) (single file) or via regular plugin installation means.
3. SSH to your server (some providers give you a console for this, otherwise do so manually) and navigate to your WP installation directory.
4. You should both take a backup (`wp wcsr backup`) and dry run the recalculate command (`wp wcsr recalculate --dry-run`) before you run for real with `wp wcsr recalculate`.
6. You'll see a success message upon successful recalculation! 🎉

## Usage

### `backup`
```
wp wcsr backup
```
Does not need any parameters. A SQL dump of subscription rows (across `wp_posts`, `wp_post_meta`, `woocommerce_order_items`, and `woocommerce_order_itemmeta`) is assembled into a file prefixed `wcsr_backup_<dd-mm-yy_hh-mm-ss>.sql` in your WP content directory (usually `/wp-content`).

### `restore`
```
wp wcsr restore --file=<file>
```

##### `--file=<file>`
> **Required.** Specify a particular backup file to restore from. If omitted, the most recent backup will be used.
* Type: string

### `recalculate`
```
wp wcsr recalculate [--dry-run] [--id=<subscription_id>]
```

##### `--dry-run`
> Perform a dry run without writing changes to the database (you may find this useful if you want to test if your store's VAT/tax settings are correctly applied here).
* Type: boolean
* Default: false

##### `--id=<subscription_id>`
> Specify a single subscription ID to recalculate. If omitted, process all active subscriptions.
* Type: integer
* Default: null
