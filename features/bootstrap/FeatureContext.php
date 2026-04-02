<?php

use WP_CLI\Tests\Context\FeatureContext as BaseFeatureContext;

class FeatureContext extends BaseFeatureContext {

	/**
	 * Copy the WC/WCS stubs mu-plugin into the test WP install.
	 *
	 * @Given the WooCommerce stubs are loaded
	 */
	public function given_woocommerce_stubs_loaded() {
		$run_dir = $this->variables['RUN_DIR'];
		$mu_dir  = $run_dir . '/wp-content/mu-plugins';

		if ( ! is_dir( $mu_dir ) ) {
			mkdir( $mu_dir, 0777, true );
		}

		copy(
			dirname( __DIR__ ) . '/extra/wcsr-test-stubs.php',
			$mu_dir . '/wcsr-test-stubs.php'
		);

		// Run a quick wp eval to trigger the init hook and create the WC tables.
		$this->proc( 'wp eval "do_action(\'init\');"' )->run_check();
	}

	/**
	 * Create a fake WooCommerce product stored in wp_posts + postmeta.
	 *
	 * @Given a product :title with price :price
	 */
	public function given_a_product( $title, $price ) {
		$php = sprintf(
			'$id = wp_insert_post(["post_title" => %s, "post_type" => "product", "post_status" => "publish"]);' .
			'update_post_meta($id, "_price", %s);' .
			'update_post_meta($id, "_regular_price", %s);' .
			'update_post_meta($id, "_tax_class", "");' .
			'echo $id;',
			var_export( $title, true ),
			var_export( $price, true ),
			var_export( $price, true )
		);

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['PRODUCT_ID'] = trim( $result->stdout );
	}

	/**
	 * Create a fake subscription (shop_subscription post) with a line item.
	 *
	 * @Given an active subscription exists for product :product_id with price :price
	 */
	public function given_an_active_subscription( $product_id, $price ) {
		$product_id = $this->replace_variables( $product_id );
		$price      = $this->replace_variables( $price );

		$php = <<<PHP
global \$wpdb;
\$sub_id = wp_insert_post([
    'post_type'   => 'shop_subscription',
    'post_status' => 'wc-active',
    'post_title'  => 'Subscription',
]);
update_post_meta(\$sub_id, '_order_total', '{$price}');

\$wpdb->insert("{$this->wc_table('woocommerce_order_items')}", [
    'order_item_name' => 'Line Item',
    'order_item_type' => 'line_item',
    'order_id'        => \$sub_id,
]);
\$item_id = \$wpdb->insert_id;

\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_product_id',    'meta_value' => '{$product_id}']);
\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_subtotal', 'meta_value' => '{$price}']);
\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_total',    'meta_value' => '{$price}']);

echo \$sub_id;
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_ID'] = trim( $result->stdout );
	}

	/**
	 * Create a subscription with a specific status.
	 *
	 * @Given a :status subscription exists for product :product_id with price :price
	 */
	public function given_a_subscription_with_status( $status, $product_id, $price ) {
		$product_id = $this->replace_variables( $product_id );
		$price      = $this->replace_variables( $price );

		$php = <<<PHP
global \$wpdb;
\$sub_id = wp_insert_post([
    'post_type'   => 'shop_subscription',
    'post_status' => 'wc-{$status}',
    'post_title'  => 'Subscription',
]);
update_post_meta(\$sub_id, '_order_total', '{$price}');

\$wpdb->insert("{$this->wc_table('woocommerce_order_items')}", [
    'order_item_name' => 'Line Item',
    'order_item_type' => 'line_item',
    'order_id'        => \$sub_id,
]);
\$item_id = \$wpdb->insert_id;

\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_product_id',    'meta_value' => '{$product_id}']);
\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_subtotal', 'meta_value' => '{$price}']);
\$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_total',    'meta_value' => '{$price}']);

echo \$sub_id;
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_ID'] = trim( $result->stdout );
	}

	/**
	 * Create multiple subscriptions for bulk-update tests.
	 *
	 * @Given :count active subscriptions exist for product :product_id with price :price
	 */
	public function given_multiple_active_subscriptions( $count, $product_id, $price ) {
		$product_id = $this->replace_variables( $product_id );
		$price      = $this->replace_variables( $price );
		$count      = (int) $count;

		$php = <<<PHP
global \$wpdb;
\$ids = [];
for (\$i = 0; \$i < {$count}; \$i++) {
    \$sub_id = wp_insert_post([
        'post_type'   => 'shop_subscription',
        'post_status' => 'wc-active',
        'post_title'  => 'Subscription ' . (\$i + 1),
    ]);
    update_post_meta(\$sub_id, '_order_total', '{$price}');

    \$wpdb->insert("{$this->wc_table('woocommerce_order_items')}", [
        'order_item_name' => 'Line Item',
        'order_item_type' => 'line_item',
        'order_id'        => \$sub_id,
    ]);
    \$item_id = \$wpdb->insert_id;

    \$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_product_id',    'meta_value' => '{$product_id}']);
    \$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_subtotal', 'meta_value' => '{$price}']);
    \$wpdb->insert("{$this->wc_table('woocommerce_order_itemmeta')}", ['order_item_id' => \$item_id, 'meta_key' => '_line_total',    'meta_value' => '{$price}']);

    \$ids[] = \$sub_id;
}
echo implode(',', \$ids);
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_IDS'] = trim( $result->stdout );
	}

	/**
	 * Change a product's price in postmeta.
	 *
	 * @Given the product :product_id price is changed to :new_price
	 */
	public function given_product_price_changed( $product_id, $new_price ) {
		$product_id = $this->replace_variables( $product_id );

		$php = sprintf(
			'update_post_meta(%s, "_price", %s); update_post_meta(%s, "_regular_price", %s);',
			$product_id,
			var_export( $new_price, true ),
			$product_id,
			var_export( $new_price, true )
		);

		$this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
	}

	/**
	 * Verify a subscription's line item total in the database.
	 *
	 * @Then the subscription :subscription_id should have line item total :expected_price
	 */
	public function then_subscription_line_item_total( $subscription_id, $expected_price ) {
		$subscription_id = $this->replace_variables( $subscription_id );

		$php = <<<PHP
global \$wpdb;
\$item_id = \$wpdb->get_var("SELECT order_item_id FROM {$this->wc_table('woocommerce_order_items')} WHERE order_id = {$subscription_id} AND order_item_type = 'line_item' LIMIT 1");
echo \$wpdb->get_var("SELECT meta_value FROM {$this->wc_table('woocommerce_order_itemmeta')} WHERE order_item_id = {\$item_id} AND meta_key = '_line_total'");
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$actual = trim( $result->stdout );

		if ( $actual !== $expected_price ) {
			throw new \Exception(
				"Expected line item total '{$expected_price}', got '{$actual}'."
			);
		}
	}

	/**
	 * Alias — verify a subscription's line item has NOT changed.
	 *
	 * @Then the subscription :subscription_id should still have line item total :expected_price
	 */
	public function then_subscription_still_has_total( $subscription_id, $expected_price ) {
		$this->then_subscription_line_item_total( $subscription_id, $expected_price );
	}

	/**
	 * @Then the backup file should exist in wp-content
	 */
	public function then_backup_file_exists() {
		$run_dir = $this->variables['RUN_DIR'];

		$php = 'echo implode("\n", glob(WP_CONTENT_DIR . "/wcsr_backup_*.sql"));';
		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$files  = array_filter( explode( "\n", trim( $result->stdout ) ) );

		if ( empty( $files ) ) {
			throw new \Exception( 'No wcsr_backup_*.sql file found in wp-content.' );
		}

		$this->variables['BACKUP_FILE'] = basename( $files[0] );
	}

	/**
	 * @Then the backup file should contain SQL for subscription :subscription_id
	 */
	public function then_backup_contains_subscription( $subscription_id ) {
		$subscription_id = $this->replace_variables( $subscription_id );
		$run_dir         = $this->variables['RUN_DIR'];
		$backup_file     = $this->variables['BACKUP_FILE'];
		$path            = $run_dir . '/wp-content/' . $backup_file;

		if ( ! file_exists( $path ) ) {
			throw new \Exception( "Backup file not found: {$path}" );
		}

		$contents = file_get_contents( $path );

		if ( strpos( $contents, 'INSERT INTO' ) === false ) {
			throw new \Exception( 'Backup file contains no INSERT statements.' );
		}

		if ( strpos( $contents, (string) $subscription_id ) === false ) {
			throw new \Exception(
				"Backup file does not reference subscription ID {$subscription_id}."
			);
		}
	}

	/**
	 * @Then the backup file should not exist in wp-content
	 */
	public function then_backup_file_deleted() {
		$run_dir     = $this->variables['RUN_DIR'];
		$backup_file = $this->variables['BACKUP_FILE'];
		$path        = $run_dir . '/wp-content/' . $backup_file;

		if ( file_exists( $path ) ) {
			throw new \Exception( "Backup file should have been deleted: {$path}" );
		}
	}

	/**
	 * Helper: return the prefixed WC table name for use in eval'd PHP.
	 *
	 * Because the table prefix is only known inside the WP process, we use
	 * $wpdb->prefix in the eval'd code. This helper returns the string
	 * expression to embed in heredoc PHP.
	 */
	private function wc_table( $table ) {
		// This string will be embedded inside PHP code that runs via `wp eval`.
		// It uses $wpdb->prefix which is available in that context.
		return '{$wpdb->prefix}' . $table;
	}
}
