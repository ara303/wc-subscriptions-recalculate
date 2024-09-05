<?php
/**
 * Plugin Name: WC Subscriptions Recalculate
 * Version: 1.0.1
 * Description: Bulk update existing WooCommerce Subscriptions when the prices of products change, via a WP-CLI command (`wp wcsr recalculate`).
 * Author: ara303 
 * Author URI: http://github.com/ara303
 * Requires at least: 6.0
 * Tested up to: 6.6.1
 */

if ( ! defined( 'WP_CLI' ) ) {
    return;
}

class WC_Subscriptions_Recalculate {
    public function recalculate( $args, $assoc_args ) {
        $dry_run = isset( $assoc_args['dry-run'] ) ?: false;
        
        if( ! class_exists( 'WC_Subscriptions' ) ){
            WP_CLI::error( "WooCommerce Subscriptions is not active." );
        }

        $subscriptions = get_posts([
            'post_type'   => 'shop_subscription',
            'post_status' => 'wc-active',
            'numberposts' => -1, // Because I'm running this inside WP-CLI I'm not as worried about suboptimally performing stuff like this!
        ]);

        if( ! $subscriptions ){
            WP_CLI::success( "No active subscriptions found." );
            return;
        }

        foreach( $subscriptions as $subscription_post ){
            $subscription = wcs_get_subscription( $subscription_post->ID );

            if( ! $subscription ){
                WP_CLI::warning( "Subscription ID {$subscription_post->ID} not found." );
                continue;
            }

            foreach( $subscription->get_items() as $item_id => $item ){
                $product_id = $item->get_product_id();
                $product = wc_get_product( $product_id );

                if( ! $product ){
                    WP_CLI::warning( "Product ID {$product_id} not found within subscription ID {$subscription_post->ID}." );
                    continue;
                }

                $old_price = $item->get_total();

                $new_price_incl_tax = wc_get_price_including_tax( $product );
                $new_price_excl_tax = wc_get_price_excluding_tax( $product );
                $tax_option = get_option( 'woocommerce_tax_display_shop' );
                if( $tax_option === "incl" ){
                    $new_price = $new_price_incl_tax;
                } else {
                    $new_price = $new_price_excl_tax;
                }

                if( ! $dry_run ){
                    $item->set_subtotal( $new_price );
                    $item->set_total( $new_price );
                    $item->save();

                    WP_CLI::log( "Price for subscription ID {$subscription_post->ID} updated to {$new_price} (was {$old_price})." );
                } else {
                    WP_CLI::log( "Dry run mode. Price for subscription ID {$subscription_post->ID} WAS NOT updated to {$new_price} (was {$old_price})." );
                }
                    
            }

            if( ! $dry_run ){
                $subscription->calculate_totals();
                $subscription->save();
    
                WP_CLI::success( "Subscription ID {$subscription_post->ID} updated." );
            }
        }

        WP_CLI::success( "WooCommmerce Subscriptions Recalculate ran successfully!" );
    }
}

WP_CLI::add_command( "wcsr recalculate", "WC_Subscriptions_Recalculate" );
