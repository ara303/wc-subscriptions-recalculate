<?php
/**
 * Plugin Name: WC Subscriptions Recalculate
 * Version: 1.180925
 * Description: Bulk update existing WooCommerce Subscriptions when the prices of products change, via WP-CLI commands.
 * Author: ara303
 * Author URI: http://github.com/ara303
 * Tested up to: 6.8.2
 */
if ( ! defined( 'WP_CLI' ) ) {
    return;
}

class WC_Subscriptions_Recalculate {
    private $backup_file;

    public function __construct() {
        $this->backup_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'wcsr_backup_' . date('Y-m-d_H-i-s') . '.sql';
    }

    /**
     * Get subscriptions based on ID or status filter.
     *
     * @param int|false $subscription_id Specific subscription ID or false for all
     * @param string $subscription_status Subscription status filter
     * @return array Array of subscription objects
     */
    private function get_subscriptions( $subscription_id, $subscription_status ){
        if( $subscription_id ){
            $subscriptions = array( wcs_get_subscription( $subscription_id ) );
        } else {
            $subscriptions = wcs_get_subscriptions([
                'subscriptions_per_page' => -1,
                'subscription_status'    => $subscription_status,
            ]);
        }

        if( ! $subscriptions ){
            WP_CLI::error( "No subscriptions found." );
            return;
        }

        return $subscriptions;
    }

/**
 * Update subscription prices to match current product prices.
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : Preview changes without writing to database.
 *
 * [--id=<subscription_id>]
 * : Update a specific subscription by ID.
 *
 * [--status=<subscription_status>]
 * : Filter subscriptions by status.
 * ---
 * default: any
 * options:
 *   - any
 *   - active
 *   - cancelled
 *   - suspended
 *   - expired
 *   - pending
 *   - trash
 * ---
 *
 * ## EXAMPLES
 *
 *     # Update all active subscriptions
 *     wp wcsr update --status=active
 *
 *     # Preview changes for all subscriptions
 *     wp wcsr update --dry-run
 *
 *     # Update a specific subscription
 *     wp wcsr update --id=123
 *
 * @when after_wp_load
 */
    public function update( $args, $assoc_args ) {
        // Parse command arguments
        $subscription_id     = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : false;
        $subscription_status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'any';
        $dry_run             = isset( $assoc_args['dry-run'] ) ?: false;

        // Validate subscription status parameter
        if( ! in_array( $subscription_status, ['any', 'active', 'cancelled', 'suspended', 'expired', 'pending', 'trash'], true ) ){
            WP_CLI::error( "Invalid subscription status: {$subscription_status}." );
            return;
        }

        // Retrieve subscriptions based on filters
        $subscriptions = $this->get_subscriptions( $subscription_id, $subscription_status );

        // Process each subscription
        foreach( $subscriptions as $subscription ){
            $subscription_id = $subscription->get_id();
            $subscription    = wcs_get_subscription( $subscription_id );

            // Update each line item in the subscription
            foreach( $subscription->get_items() as $item ){
                $product = wc_get_product( $item->get_product_id() );
                
                if( ! $product ){
                    WP_CLI::warning( "No products found for subscription ID: {$subscription_id}." );
                    continue;
                }

                // Compare current subscription price with product price
                $old_price = $item->get_subtotal();
                $new_price = $product->get_price();
                $different = $old_price !== $new_price;

                if( ! $different ){
                    WP_CLI::log( "#{$subscription_id}: No difference in price." );
                    continue;
                }

                // Calculate taxes for the new price
                $tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
                $taxes     = WC_Tax::calc_tax( $new_price, $tax_rates, wc_prices_include_tax() );
                $new_price + array_sum( $taxes );

                // Update subscription item if not in dry-run mode
                if( ! $dry_run ){
                    $item->set_taxes([
                        'total'    => $taxes,
                        'subtotal' => $taxes
                    ]);
                    $item->set_subtotal( $new_price );
                    $item->set_total( $new_price );
                    $item->save();

                    // Recalculate and save subscription totals
                    $subscription->set_total( $new_price );
                    $subscription->calculate_taxes();
                    $subscription->save();
                }

                WP_CLI::log( "#{$subscription_id}: Total {$old_price} -> {$new_price}." );
            }
        }
        
        WP_CLI::success( "Completed" . ( $dry_run ? ' but --dry-run flag means no changes were made' : '' ) . "." );
    }

    /**
     * Create a backup of subscription data.
     *
     * ## OPTIONS
     *
     * [--id=<subscription_id>]
     * : Backup a specific subscription by ID.
     *
     * [--status=<subscription_status>]
     * : Filter subscriptions by status (any, active, cancelled, suspended, expired, pending, trash).
     * ---
     * default: any
     * ---
     *
     * ## EXAMPLES
     *
     *     # Backup all subscriptions
     *     wp wcsr create
     *
     *     # Backup only active subscriptions
     *     wp wcsr create --status=active
     *
     *     # Backup a specific subscription
     *     wp wcsr create --id=123
     *
     * @when after_wp_load
     */
    public function create( $args, $assoc_args ){
        // Parse command arguments
        $subscription_id     = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : false;
        $subscription_status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'any';

        // Validate subscription status parameter
        if( ! in_array( $subscription_status, ['any', 'active', 'cancelled', 'suspended', 'expired', 'pending', 'trash'], true ) ){
            WP_CLI::error( "Invalid subscription status: {$subscription_status}." );
            return;
        }

        // Retrieve subscriptions based on filters
        $subscriptions = $this->get_subscriptions( $subscription_id, $subscription_status );

        global $wpdb;

        $dump = "";

        // Build SQL dump for each subscription and its related data
        foreach( $subscriptions as $subscription ){
            $subscription_id = $subscription->get_id();

            // Backup main subscription post
            $posts_row = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE ID = {$subscription_id}", ARRAY_A );
            $dump .= $this->create_insert_query( $wpdb->posts, $posts_row );

            // Backup post metadata
            $post_meta_row = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = {$subscription_id}", ARRAY_A );
            foreach( $post_meta_row as $meta ){
                $dump .= $this->create_insert_query($wpdb->postmeta, $meta );
            }

            // Backup WooCommerce order items
            $woocommerce_order_items_row = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = {$subscription_id}", ARRAY_A );

            foreach ($woocommerce_order_items_row as $item) {
                $dump .= $this->create_insert_query( $wpdb->prefix . 'woocommerce_order_items', $item );

                // Backup order item metadata
                $woocommerce_order_itemmeta_row = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$item['order_item_id']}", ARRAY_A );
                foreach( $woocommerce_order_itemmeta_row as $meta ){
                    $dump .= $this->create_insert_query( $wpdb->prefix . 'woocommerce_order_itemmeta', $meta );
                }
            }
        }

        // Write backup to file
        file_put_contents( $this->backup_file, $dump );

        WP_CLI::log( "Successfully created dump of affected rows at: " . $this->backup_file );
    }

    /**
     * Create SQL INSERT query for a table row.
     *
     * @param string $table Table name
     * @param array $data Row data as associative array
     * @return string SQL INSERT query
     */
    private function create_insert_query( $table, $data ){
        global $wpdb;
        $fields = implode( ', ', array_keys( $data ) );
        $values = implode( ', ', array_map( function( $value ) use ( $wpdb ){
            return "'" . $wpdb->_real_escape($value) . "'";
        }, $data ) );
        return "INSERT INTO `{$table}` ({$fields}) VALUES ({$values});\n";
    }

    /**
     * Restore subscription data from a backup file.
     *
     * ## OPTIONS
     *
     * --file=<filename>
     * : Backup filename (located in wp-content directory).
     *
     * [--delete]
     * : Delete the backup file after successful restoration.
     *
     * ## EXAMPLES
     *
     *     # Restore from backup
     *     wp wcsr restore --file=wcsr_backup_2024-01-20_10-30-00.sql
     *
     *     # Restore and delete backup file
     *     wp wcsr restore --file=wcsr_backup_2024-01-20_10-30-00.sql --delete
     *
     * @when after_wp_load
     */
    public function restore( $args, $assoc_args ){
        // Parse and validate file parameter
        $file = $assoc_args['file'];
        if( isset( $file ) ){
            $backup_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file;
        } else {
            WP_CLI::error( "No backup file given. Use syntax: `wp wcsr restore --file=<file_name> (no directories needed)`.");
        }

        // Verify backup file exists
        if( ! file_exists( $backup_file ) ){
            WP_CLI::error( "Backup file not found: " . $backup_file );
            return;
        }

        // Read and parse SQL dump
        $sql = file_get_contents( $backup_file );
        $sql_lines = explode( ";\n", $sql );

        global $wpdb;

        // Execute each SQL statement to restore data
        foreach( $sql_lines as $sql_line ){
            if( ! empty( trim( $sql_line ) ) ){
                $wpdb->query( $sql_line );
            }
        }

        WP_CLI::success( "Successfully restored from given file." );

        // Optionally delete backup file after restoration
        if( isset( $assoc_args['delete'] ) ){
            if( unlink( $backup_file ) ){
                WP_CLI::log( "Deleted backup file: " . basename( $backup_file ) );
            }
        }
    }
}

$wcsr = new WC_Subscriptions_Recalculate();
WP_CLI::add_command("wcsr update", [$wcsr, 'update']);
WP_CLI::add_command("wcsr create", [$wcsr, 'create']);
WP_CLI::add_command("wcsr restore", [$wcsr, 'restore']);
WP_CLI::add_command("wcsr recalculate", [$wcsr, 'update']);
WP_CLI::add_command("wcsr backup", [$wcsr, 'create']);
