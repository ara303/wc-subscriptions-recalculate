<?php
/**
 * Plugin Name: WC Subscriptions Recalculate
 * Version: 0.0.1-alpha
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
        $subscription_id = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : false;
        $dry_run = isset( $assoc_args['dry-run'] ) ?: false;
        
        if( ! class_exists( 'WC_Subscriptions' ) ){
            WP_CLI::error( "WooCommerce Subscriptions is not active." );
        }

        if( $subscription_id ) {
            $subscriptions = [ wcs_get_subscription( $subscription_id ) ];
        } else {
            $subscriptions = get_posts([
                'post_type'   => 'shop_subscription',
                'post_status' => 'wc-active',
                'numberposts' => -1,
            ]);
        }

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

            if( $subscription_id ){
                WP_CLI::log( "Single mode (only operating on for scription ID {$subscription_id})." );
            }

            foreach( $subscription->get_items() as $item_id => $item ){
                $product_id = $item->get_product_id();
                $product = wc_get_product( $product_id );
                $new_price = $product->get_price();

                if( ! $product ){
                    WP_CLI::warning( "Product ID {$product_id} not found within subscription ID {$subscription_post->ID}." );
                    continue;
                }

                $tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
                $taxes = WC_Tax::calc_tax( $new_price, $tax_rates, wc_prices_include_tax() );                

                if( ! $dry_run ){
                    $item->set_taxes([
                        'total'    => $taxes,
                        'subtotal' => $taxes,
                    ]);

                    $item->set_subtotal( $new_price );
                    $item->set_total( $new_price );
                    $item->save();

                    $subscription_total += $new_price + array_sum( $taxes );

                    WP_CLI::log( "Price for subscription ID {$subscription_post->ID} updated to {$new_price}." );
                } else {
                    WP_CLI::log( "-- Dry run -- Price for subscription ID {$subscription_post->ID} updated to {$new_price}." );
                }
                    
            }

            if( ! $dry_run ){
                $subscription->set_total( $subscription_total );
                $subscription->calculate_taxes();
                $subscription->save();
    
                WP_CLI::success( "Subscription ID {$subscription_post->ID} updated." );
            }
        }

        WP_CLI::success( "WooCommmerce Subscriptions Recalculate ran successfully!" );
    }
}

WP_CLI::add_command( "wcsr", "WC_Subscriptions_Recalculate" );
