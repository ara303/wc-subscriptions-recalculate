- **`create`** - Create a backup of subscription data
- **`update`** - Update subscription prices to match current product prices
- **`restore`** - Restore subscription data from a backup file

### `create`
```
wp wcsr create [--id=<subscription_id>] [--status=<subscription_status>]
```

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