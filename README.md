# wc-subscriptions-recalculate
> [!IMPORTANT]
 VAT/tax calculation is only suitable for my usecase (enter prices without and only add it at checkout, relying on built-in tax calculation methods). Depending on what regions and tax laws you have to comply with this may or may not apply to you.

Bulk update existing WooCommerce Subscriptions when the prices of products change via WP-CLI (verbose by default).

I do this through WP-CLI because depending on how many subscriptions you might have it didn't seem right to waste resources running this through the WP-Admin UI, and using WP-CLI makes it a breeze to keep a log of each item that's been updated in case something seems to be wrong.

## Installation
2. Install either as an [MU-Plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) (single file) or via regular plugin installation means.
3. SSH to your server (some providers give you a console for this, otherwise do so manually) and navigate to your WP installation directory.
4. You should both create a backup (`wp wcsr create`) and dry run the update command (`wp wcsr update --dry-run`) before you run for real with `wp wcsr update`.
6. You'll see a success message upon successful update! 🎉

## Usage

This plugin uses standard WordPress CRUD (Create, Read, Update, Delete) terminology for its commands:

- **`create`** - Create a backup of subscription data
- **`update`** - Update subscription prices to match current product prices
- **`restore`** - Restore subscription data from a backup file

### `create`
```
wp wcsr create [--id=<subscription_id>] [--status=<subscription_status>]
```

> [!CAUTION]
 This and `restore` work on databases of a relatively large size (~100 MB), but may not for much larger databases if you have thousands (or even more) subscriptions. **As a just-in-case precaution,** also run the WP-CLI commands to [export](https://developer.wordpress.org/cli/commands/db/export/) and [import](https://developer.wordpress.org/cli/commands/db/import/) databases in entirety.

Create an SQL dump of subscriptions which may be affected across `wp_posts`, `wp_post_meta`, `woocommerce_order_items`, and `woocommerce_order_itemmeta`. A file named `wcsr_backup_<dd-mm-yy_hh-mm-ss>.sql` will be created in your WP content directory (normally `/wp-content/`).

##### `--id=<subscription_id>`
> Specify a single subscription ID. If omitted, all subscriptions (observing `--status` if set) are backed up.
* Type: integer
* Default: null

##### `--status=<subscription_status>`
> Specify a subscription status. If omitted, all subscriptions are processed. See [WooCommerce Subscrptions documentation](https://woocommerce.com/document/subscriptions/develop/action-reference/#subscription-status-change-actions) for valid subscription statuses to use.
* Type: string
* Default: `any`

### `restore`
```
wp wcsr restore --file=<filename> [--delete]
```

Restore subscription data from a previously created backup file.

##### `--file=<filename>`
**Required.** Specify the SQL dump of subscriptions that will be restored (located in wp-content directory).
* Type: string

##### `--delete`
Deletes the file once restored from. Note: Does not confirm successful restoration in case of database issue or other error.
* Type: boolean
* Default: unset

### `update`
```
wp wcsr update [--dry-run] [--id=<subscription_id>] [--status=<subscription_status>]
```
Update WC Subscriptions to match current product prices, taking into account VAT/tax if in use.

##### `--dry-run`
Perform a dry run without writing changes to the database (you may find this useful if you want to test if your store's VAT/tax settings are correctly applied here).
* Type: boolean
* Default: unset

##### `--id=<subscription_id>`
> Specify a single subscription ID. If omitted, all subscriptions (observing `--status` if set) are processed.
* Type: integer
* Default: null

##### `--status=<subscription_status>`
> Specify a single subscription status. If omitted, all subscriptions are processed. See [WooCommerce Subscrptions documentation](https://woocommerce.com/document/subscriptions/develop/action-reference/#subscription-status-change-actions) for valid subscription statuses to use.
* Type: string
* Default: `any`

## Backward Compatibility

For backward compatibility, the legacy command names are still supported:
- `wp wcsr backup` → Use `wp wcsr create` instead
- `wp wcsr recalculate` → Use `wp wcsr update` instead
