<?php
/**
 * Plugin Name: WC Subscriptions Recalculate
 * Version: 1.0.0
 * Description: Bulk update existing WooCommerce Subscriptions when the prices of products change, via a WP-CLI command (`wcsr recalculate`). Verbose by default.
 * Author: ara303 
 * Author URI: http://github.com/ara303
 * Requires at least: 6.0
 * Tested up to: 6.6.1
 */

if ( ! defined( 'WP_CLI' ) ) {
    return;
}

class WC_Subscriptions_Recalculate {
    public function recalculate() {
        if( ! class_exists( 'WC_Subscriptions' ) ){
            WP_CLI::error( "WooCommerce Subscriptions is not active." );
        }

        $subscriptions = get_posts([
            'post_type'   => 'shop_subscription',
            'post_status' => 'wc-active',
            'numberposts' => -1, // Because I'm running this inside WP-CLI I'm not as worried about suboptimally performing stuff like this!
        ]);

        if( ! $subscriptions ){
            WP_CLI::success( "No active subscriptions found. Nothing has been recalculated." );
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
                    WP_CLI::warning( "No product ID {$product_id} found for subscription ID {$subscription_post->ID}. That product could have been deleted." );
                    continue;
                }

                $old_price = $item->get_total();
                $new_price = $product->get_price();

                $item->set_subtotal( $new_price );
                $item->set_total( $new_price );
                $item->save();

                WP_CLI::log( "Set new price for subscription ID: {$subscription_post->ID}, previously: {$old_price} and now: {$new_price}." );
            }

            $subscription->calculate_totals();
            $subscription->save();

            WP_CLI::success( "Subscription ID {$subscription_post->ID} recalculated." );
        }

        WP_CLI::success( "All active subscriptions recalculated." );
    }
}

WP_CLI::add_command( "wcsr recalculate", "WC_Subscriptions_Recalculate" );
