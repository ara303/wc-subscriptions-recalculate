ara303/wc-subscriptions-recalculate
===================================

Recalculate WooCommerce Subscriptions in bulk for existing subscriptions (via WP-CLI)



Quick links: [Using](#using) | [Installing](#installing) | [Notes](#notes)

## Using

This package implements the following commands:

### wp wcsr update

Update subscription prices to match current product prices.

~~~
wp wcsr update [--dry-run] [--id=<subscription_id>] [--status=<subscription_status>] [--format=<format>]
~~~

**OPTIONS**

	[--dry-run]
		Preview changes without writing to database.

	[--id=<subscription_id>]
		Update a specific subscription by ID.

	[--status=<subscription_status>]
		Filter subscriptions by status.
		---
		default: any
		options:
		  - any
		  - active
		  - cancelled
		  - suspended
		  - expired
		  - pending
		  - trash
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - yaml
		  - ids
		  - count
		---

**EXAMPLES**

    # Update all active subscriptions
    wp wcsr update --status=active

    # Preview changes for all subscriptions
    wp wcsr update --dry-run

    # Update a specific subscription
    wp wcsr update --id=123



### wp wcsr create

Create a backup of subscription data.

~~~
wp wcsr create [--id=<subscription_id>] [--status=<subscription_status>]
~~~

**OPTIONS**

	[--id=<subscription_id>]
		Backup a specific subscription by ID.

	[--status=<subscription_status>]
		Filter subscriptions by status (any, active, cancelled, suspended, expired, pending, trash).
		---
		default: any
		---

**EXAMPLES**

    # Backup all subscriptions
    wp wcsr create

    # Backup only active subscriptions
    wp wcsr create --status=active

    # Backup a specific subscription
    wp wcsr create --id=123



### wp wcsr restore

Restore subscription data from a backup file.

~~~
wp wcsr restore --file=<filename> [--delete]
~~~

**OPTIONS**

	--file=<filename>
		Backup filename (located in wp-content directory).

	[--delete]
		Delete the backup file after successful restoration.

**EXAMPLES**

    # Restore from backup
    wp wcsr restore --file=wcsr_backup_2024-01-20_10-30-00.sql

    # Restore and delete backup file
    wp wcsr restore --file=wcsr_backup_2024-01-20_10-30-00.sql --delete

## Installing

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

## Notes

> [!IMPORTANT]
 VAT/tax calculation is only suitable for my usecase (enter prices without and only add it at checkout, relying on built-in tax calculation methods). Depending on what regions and tax laws you have to comply with this may or may not apply to you.

You should run the WP-CLI commands for [`export`](https://developer.wordpress.org/cli/commands/db/export/) and [`import`](https://developer.wordpress.org/cli/commands/db/import/) just in case.


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
