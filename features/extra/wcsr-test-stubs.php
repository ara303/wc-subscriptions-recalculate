<?php
/**
 * WooCommerce & WooCommerce Subscriptions stubs for Behat testing.
 *
 * Loaded as a mu-plugin in the test WP install. Provides minimal fake
 * implementations of WC/WCS functions and classes that the plugin relies on,
 * backed by real WP tables so that the backup/restore commands work unmodified.
 *
 * Tables used:
 *   wp_posts            — subscriptions as post_type 'shop_subscription', products as 'product'
 *   wp_postmeta         — _price, _regular_price, _tax_class, _order_total
 *   woocommerce_order_items     — line items linking subscriptions to products
 *   woocommerce_order_itemmeta  — line item amounts (_product_id, _line_subtotal, _line_total, etc.)
 */

// Guard against double-loading.
if ( defined( 'WCSR_TEST_STUBS_LOADED' ) ) {
	return;
}
define( 'WCSR_TEST_STUBS_LOADED', true );

// ---------------------------------------------------------------------------
// Bootstrap: create the WooCommerce tables if they don't exist yet.
// ---------------------------------------------------------------------------
add_action( 'init', function () {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woocommerce_order_items (
		order_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		order_item_name TEXT NOT NULL,
		order_item_type VARCHAR(200) NOT NULL DEFAULT '',
		order_id BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (order_item_id),
		KEY order_id (order_id)
	) {$charset_collate}" );

	$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woocommerce_order_itemmeta (
		meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		order_item_id BIGINT UNSIGNED NOT NULL,
		meta_key VARCHAR(255) DEFAULT NULL,
		meta_value LONGTEXT DEFAULT NULL,
		PRIMARY KEY (meta_id),
		KEY order_item_id (order_item_id),
		KEY meta_key (meta_key(32))
	) {$charset_collate}" );
}, 0 );

// ---------------------------------------------------------------------------
// Fake WC_Tax class.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WC_Tax' ) ) {
	class WC_Tax {
		public static function get_rates( $tax_class = '' ) {
			// Return empty — no tax rates configured in tests by default.
			return [];
		}

		public static function calc_tax( $price, $rates, $price_includes_tax = false ) {
			// With no rates, taxes are zero.
			return [];
		}
	}
}

// ---------------------------------------------------------------------------
// Fake order line-item object.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WCSR_Fake_Item' ) ) {
	class WCSR_Fake_Item {
		private $order_item_id;
		private $product_id;
		private $subtotal;
		private $total;
		private $taxes = [];

		public function __construct( $order_item_id ) {
			global $wpdb;
			$this->order_item_id = (int) $order_item_id;

			$meta_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d",
					$this->order_item_id
				),
				ARRAY_A
			);

			foreach ( $meta_rows as $row ) {
				switch ( $row['meta_key'] ) {
					case '_product_id':
						$this->product_id = (int) $row['meta_value'];
						break;
					case '_line_subtotal':
						$this->subtotal = $row['meta_value'];
						break;
					case '_line_total':
						$this->total = $row['meta_value'];
						break;
				}
			}
		}

		public function get_product_id() {
			return $this->product_id;
		}

		public function get_subtotal() {
			return $this->subtotal;
		}

		public function get_total() {
			return $this->total;
		}

		public function set_subtotal( $value ) {
			$this->subtotal = $value;
		}

		public function set_total( $value ) {
			$this->total = $value;
		}

		public function set_taxes( $taxes ) {
			$this->taxes = $taxes;
		}

		public function save() {
			global $wpdb;
			$this->update_meta( '_line_subtotal', $this->subtotal );
			$this->update_meta( '_line_total', $this->total );
			$this->update_meta( '_line_tax', maybe_serialize( $this->taxes ) );
		}

		private function update_meta( $key, $value ) {
			global $wpdb;
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key = %s",
					$this->order_item_id,
					$key
				)
			);
			if ( $existing ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					[ 'meta_value' => $value ],
					[ 'order_item_id' => $this->order_item_id, 'meta_key' => $key ]
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'woocommerce_order_itemmeta',
					[ 'order_item_id' => $this->order_item_id, 'meta_key' => $key, 'meta_value' => $value ]
				);
			}
		}
	}
}

// ---------------------------------------------------------------------------
// Fake subscription object.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WCSR_Fake_Subscription' ) ) {
	class WCSR_Fake_Subscription {
		private $id;
		private $total;

		public function __construct( $post_id ) {
			$this->id    = (int) $post_id;
			$this->total = get_post_meta( $this->id, '_order_total', true );
		}

		public function get_id() {
			return $this->id;
		}

		public function get_items() {
			global $wpdb;
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = 'line_item'",
					$this->id
				),
				ARRAY_A
			);

			$items = [];
			foreach ( $rows as $row ) {
				$items[] = new WCSR_Fake_Item( $row['order_item_id'] );
			}
			return $items;
		}

		public function set_total( $value ) {
			$this->total = $value;
		}

		public function calculate_taxes() {
			// No-op in test stubs.
		}

		public function save() {
			update_post_meta( $this->id, '_order_total', $this->total );
		}
	}
}

// ---------------------------------------------------------------------------
// Fake product object.
// ---------------------------------------------------------------------------
if ( ! class_exists( 'WCSR_Fake_Product' ) ) {
	class WCSR_Fake_Product {
		private $id;

		public function __construct( $post_id ) {
			$this->id = (int) $post_id;
		}

		public function get_id() {
			return $this->id;
		}

		public function get_price() {
			return get_post_meta( $this->id, '_price', true );
		}

		public function get_tax_class() {
			return get_post_meta( $this->id, '_tax_class', true ) ?: '';
		}
	}
}

// ---------------------------------------------------------------------------
// Global WC/WCS functions.
// ---------------------------------------------------------------------------
if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'product' !== $post->post_type ) {
			return false;
		}
		return new WCSR_Fake_Product( $id );
	}
}

if ( ! function_exists( 'wcs_get_subscription' ) ) {
	function wcs_get_subscription( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'shop_subscription' !== $post->post_type ) {
			return false;
		}
		return new WCSR_Fake_Subscription( $id );
	}
}

if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
	function wcs_get_subscriptions( $args = [] ) {
		$per_page = isset( $args['subscriptions_per_page'] ) ? (int) $args['subscriptions_per_page'] : -1;
		$status   = isset( $args['subscription_status'] ) ? $args['subscription_status'] : 'any';

		$query_args = [
			'post_type'      => 'shop_subscription',
			'posts_per_page' => $per_page,
			'post_status'    => 'any',
			'fields'         => 'ids',
		];

		// Map subscription statuses to post statuses.
		if ( 'any' !== $status ) {
			$query_args['post_status'] = 'wc-' . $status;
		}

		$ids = get_posts( $query_args );

		$subscriptions = [];
		foreach ( $ids as $id ) {
			$subscriptions[ $id ] = new WCSR_Fake_Subscription( $id );
		}
		return $subscriptions;
	}
}

if ( ! function_exists( 'wc_prices_include_tax' ) ) {
	function wc_prices_include_tax() {
		return false;
	}
}
