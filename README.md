# wc-subscriptions-recalculate
> [!IMPORTANT]
> Please **take a backup first**. VAT/tax calculation is only suitable for my usecase (enter prices without and only add it at checkout, relying on built-in tax calculation methods). Depending on what regions and tax laws you have to comply with this may or may not apply to you.

Bulk update existing WooCommerce Subscriptions when the prices of products change via WP-CLI (verbose by default).

I do this through WP-CLI because depending on how many subscriptions you might have it didn't seem right to waste resources running this through the WP-Admin UI, and using WP-CLI makes it a breeze to keep a log of each item that's been updated in case something seems to be wrong.

## Installation
2. Install either as an [MU-Plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) (single file) or via regular plugin installation means.
3. SSH to your server (some providers give you a console for this, otherwise do so manually) and navigate to your WP installation directory.
4. See syntax below for the exact commands to type. A dry run is strongly recommended first.
6. You'll see a success message upon successful recalculation! 🎉

## Usage
```
wp wcsr recalculate [--dry-run] [--id=<subscription_id>]
```

### Parameters

#### `--dry-run`
> Perform a dry run without writing changes to the database (you may find this useful if you want to test if your store's VAT/tax settings are correctly applied here).
* Type: boolean
* Default: false

#### `--id=<subscription_id>`
> Specify a single subscription ID to recalculate. If omitted, process all active subscriptions.
* Type: integer
* Default: null

