<?php
/**
 * Plugin Name: WC Subscriptions Recalculate
 * Version: 1.030625
 * Description: Bulk update existing WooCommerce Subscriptions when the prices of products change, via WP-CLI commands.
 * Author: ara303
 * Author URI: http://github.com/ara303
 * Requires at least: 6.0
 * Tested up to: 6.6.1
 */
if ( ! defined( 'WP_CLI' ) ) {
    return;
}

class WC_Subscriptions_Recalculate {
    private $backup_file;

    public function __construct() {
        $this->backup_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'wcsr_backup_' . date('Y-m-d_H-i-s') . '.sql';
    }

    private function get_subscriptions( $subscription_id = false, $subscription_status ){
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

    public function recalculate( $args, $assoc_args ) {
        $subscription_id     = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : false;
        $subscription_status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'any';
        $dry_run             = isset( $assoc_args['dry-run'] ) ?: false;

        if( ! in_array( $subscription_status, ['any', 'active', 'cancelled', 'suspended', 'expired', 'pending', 'trash'], true ) ){
            WP_CLI::error( "Invalid subscription status: {$subscription_status}." );
            return;
        }

        $subscriptions = $this->get_subscriptions( $subscription_id, $subscription_status );

        foreach( $subscriptions as $subscription ){
            $subscription_id = $subscription->get_id();
            $subscription    = wcs_get_subscription( $subscription_id );

            foreach( $subscription->get_items() as $item ){
                $product = wc_get_product( $item->get_product_id() );
                
                if( ! $product ){
                    WP_CLI::warning( "No products found for subscription ID: {$subscription_id}." );
                    continue;
                }

                $old_price = $item->get_subtotal();
                $new_price = $product->get_price();
                $different = $old_price !== $new_price;

                if( ! $different ){
                    WP_CLI::log( "#{$subscription_id}: No difference in price." );
                    continue;
                }

                $tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
                $taxes     = WC_Tax::calc_tax( $new_price, $tax_rates, wc_prices_include_tax() );
                $new_price + array_sum( $taxes );

                if( ! $dry_run ){
                    $item->set_taxes([
                        'total'    => $taxes,
                        'subtotal' => $taxes
                    ]);
                    $item->set_subtotal( $new_price );
                    $item->set_total( $new_price );
                    $item->save();

                    $subscription->set_total( $new_price );
                    $subscription->calculate_taxes();
                    $subscription->save();
                }

                WP_CLI::log( "#{$subscription_id}: Total {$old_price} -> {$new_price}." );
            }
        }
        
        WP_CLI::success( "Completed" . ( $dry_run ? ' but --dry-run flag means no changes were made' : '' ) . "." );
    }

    public function backup( $args, $assoc_args ){
        $subscription_id = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : false;

        $subscriptions = $this->get_subscriptions( $subscription_id );

        global $wpdb;

        $dump = "";

        foreach( $subscriptions as $subscription ){
            $subscription_id = $subscription->get_id();

            $posts_row = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE ID = {$subscription_id}", ARRAY_A );
            $dump .= $this->create_insert_query( $wpdb->posts, $posts_row );

            $post_meta_row = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = {$subscription_id}", ARRAY_A );
            foreach( $post_meta_row as $meta ){
                $dump .= $this->create_insert_query($wpdb->postmeta, $meta );
            }

            $woocommerce_order_items_row = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = {$subscription_id}", ARRAY_A );

            foreach ($woocommerce_order_items_row as $item) {
                $dump .= $this->create_insert_query( $wpdb->prefix . 'woocommerce_order_items', $item );

                $woocommerce_order_itemmeta_row = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$item['order_item_id']}", ARRAY_A );
                foreach( $woocommerce_order_itemmeta_row as $meta ){
                    $dump .= $this->create_insert_query( $wpdb->prefix . 'woocommerce_order_itemmeta', $meta );
                }
            }
        }

        file_put_contents( $this->backup_file, $dump );

        WP_CLI::log( "Succesfully created dump of affected rows at: " . $this->backup_file );
    }

    private function create_insert_query( $table, $data ){
        global $wpdb;
        $fields = implode( ', ', array_keys( $data ) );
        $values = implode( ', ', array_map( function( $value ) use ( $wpdb ){
            return "'" . $wpdb->_real_escape($value) . "'";
        }, $data ) );
        return "INSERT INTO `{$table}` ({$fields}) VALUES ({$values});\n";
    }

    public function restore( $args, $assoc_args ){
        $file = $assoc_args['file'];
        if( isset( $file ) ){
            $backup_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $file;
        } else {
            WP_CLI::error( "No backup file given. Use syntax: `wp wcsr restore --file=<file_name> (no directories needed)`.");
        }

        if( ! file_exists( $backup_file ) ){
            WP_CLI::error( "Backup file not found: " . $backup_file );
            return;
        }

        $sql = file_get_contents( $backup_file );
        $sql_lines = explode( ";\n", $sql );

        global $wpdb;

        foreach( $sql_lines as $sql_line ){
            if( ! empty( trim( $sql_line ) ) ){
                $wpdb->query( $sql_line );
            }
        }

        WP_CLI::success( "Successfully restored from given file." );

        if( isset( $assoc_args['delete'] ) ){
            if( unlink( $backup_file ) ){
                WP_CLI::log( "Deleted backup file: " . basename( $backup_file ) );
            }
        }
    }
}

$wcsr = new WC_Subscriptions_Recalculate();
WP_CLI::add_command("wcsr recalculate", [$wcsr, 'recalculate']);
WP_CLI::add_command("wcsr backup", [$wcsr, 'backup']);
WP_CLI::add_command("wcsr restore", [$wcsr, 'restore']);
