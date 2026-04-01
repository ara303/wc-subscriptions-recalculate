<?php

use WP_CLI\Tests\Context\FeatureContext as BaseFeatureContext;
use Behat\Gherkin\Node\PyStringNode;

class FeatureContext extends BaseFeatureContext {

	/**
	 * Install and activate WooCommerce + WooCommerce Subscriptions.
	 *
	 * WooCommerce is installed from the plugin directory.
	 * WooCommerce Subscriptions must be available locally at
	 * WCS_PLUGIN_PATH env var (it's a premium plugin).
	 *
	 * @Given WooCommerce and WooCommerce Subscriptions are installed and active
	 */
	public function given_woocommerce_and_subscriptions_active() {
		$this->proc( 'wp plugin install woocommerce --activate' )->run_check();

		$wcs_path = getenv( 'WCS_PLUGIN_PATH' );

		if ( ! $wcs_path || ! is_dir( $wcs_path ) ) {
			throw new \RuntimeException(
				'WCS_PLUGIN_PATH env var must point to a local copy of the WooCommerce Subscriptions plugin directory.'
			);
		}

		$run_dir = $this->variables['RUN_DIR'];
		$dest    = $run_dir . '/wp-content/plugins/woocommerce-subscriptions';

		// Symlink the plugin into the WP install.
		symlink( $wcs_path, $dest );

		$this->proc( 'wp plugin activate woocommerce-subscriptions' )->run_check();
	}

	/**
	 * Create a simple WooCommerce product.
	 *
	 * @Given a WooCommerce product :title with price :price
	 */
	public function given_a_woocommerce_product( $title, $price ) {
		$php = sprintf(
			'$product = new WC_Product_Simple();' .
			'$product->set_name( %s );' .
			'$product->set_regular_price( %s );' .
			'$product->set_price( %s );' .
			'$product->set_status( "publish" );' .
			'$product->save();' .
			'echo $product->get_id();',
			var_export( $title, true ),
			var_export( $price, true ),
			var_export( $price, true )
		);

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['PRODUCT_ID'] = trim( $result->stdout );
	}

	/**
	 * Create a subscription for a given product.
	 *
	 * @Given an active subscription exists for product :product_id with price :price
	 */
	public function given_an_active_subscription_for_product( $product_id, $price ) {
		$product_id = $this->replace_variables( $product_id );
		$price      = $this->replace_variables( $price );

		$php = <<<PHP
\$product = wc_get_product( {$product_id} );
\$order = wc_create_order( [ 'status' => 'completed' ] );
\$subscription = wcs_create_subscription( [
    'order_id'         => \$order->get_id(),
    'status'           => 'active',
    'billing_period'   => 'month',
    'billing_interval' => 1,
] );
if ( is_wp_error( \$subscription ) ) {
    WP_CLI::error( \$subscription->get_error_message() );
}
\$item_id = \$subscription->add_product( \$product, 1, [
    'subtotal' => {$price},
    'total'    => {$price},
] );
\$subscription->set_total( {$price} );
\$subscription->save();
echo \$subscription->get_id();
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_ID'] = trim( $result->stdout );
	}

	/**
	 * Update a product's price after subscription creation.
	 *
	 * @Given the product :product_id price is changed to :new_price
	 */
	public function given_product_price_changed( $product_id, $new_price ) {
		$product_id = $this->replace_variables( $product_id );

		$php = sprintf(
			'$product = wc_get_product( %s );' .
			'$product->set_regular_price( %s );' .
			'$product->set_price( %s );' .
			'$product->save();',
			$product_id,
			var_export( $new_price, true ),
			var_export( $new_price, true )
		);

		$this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
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
\$product = wc_get_product( {$product_id} );
\$order = wc_create_order( [ 'status' => 'completed' ] );
\$subscription = wcs_create_subscription( [
    'order_id'         => \$order->get_id(),
    'status'           => '{$status}',
    'billing_period'   => 'month',
    'billing_interval' => 1,
] );
if ( is_wp_error( \$subscription ) ) {
    WP_CLI::error( \$subscription->get_error_message() );
}
\$subscription->add_product( \$product, 1, [
    'subtotal' => {$price},
    'total'    => {$price},
] );
\$subscription->set_total( {$price} );
\$subscription->save();
echo \$subscription->get_id();
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_ID'] = trim( $result->stdout );
	}

	/**
	 * Verify a subscription line item price in the database.
	 *
	 * @Then the subscription :subscription_id should have line item total :expected_price
	 */
	public function then_subscription_should_have_line_item_total( $subscription_id, $expected_price ) {
		$subscription_id = $this->replace_variables( $subscription_id );

		$php = <<<PHP
\$subscription = wcs_get_subscription( {$subscription_id} );
foreach ( \$subscription->get_items() as \$item ) {
    echo \$item->get_total();
    break;
}
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$actual = trim( $result->stdout );

		if ( $actual !== $expected_price ) {
			throw new \Exception(
				"Expected subscription line item total to be '{$expected_price}', but got '{$actual}'."
			);
		}
	}

	/**
	 * Verify a subscription line item price has NOT changed.
	 *
	 * @Then the subscription :subscription_id should still have line item total :expected_price
	 */
	public function then_subscription_should_still_have_line_item_total( $subscription_id, $expected_price ) {
		$this->then_subscription_should_have_line_item_total( $subscription_id, $expected_price );
	}

	/**
	 * Create multiple subscriptions with different products.
	 *
	 * @Given :count active subscriptions exist for product :product_id with price :price
	 */
	public function given_multiple_active_subscriptions( $count, $product_id, $price ) {
		$product_id = $this->replace_variables( $product_id );
		$price      = $this->replace_variables( $price );
		$count      = (int) $count;

		$php = <<<PHP
\$product = wc_get_product( {$product_id} );
\$ids = [];
for ( \$i = 0; \$i < {$count}; \$i++ ) {
    \$order = wc_create_order( [ 'status' => 'completed' ] );
    \$subscription = wcs_create_subscription( [
        'order_id'         => \$order->get_id(),
        'status'           => 'active',
        'billing_period'   => 'month',
        'billing_interval' => 1,
    ] );
    if ( is_wp_error( \$subscription ) ) {
        WP_CLI::error( \$subscription->get_error_message() );
    }
    \$subscription->add_product( \$product, 1, [
        'subtotal' => {$price},
        'total'    => {$price},
    ] );
    \$subscription->set_total( {$price} );
    \$subscription->save();
    \$ids[] = \$subscription->get_id();
}
echo implode( ',', \$ids );
PHP;

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$this->variables['SUBSCRIPTION_IDS'] = trim( $result->stdout );
	}

	/**
	 * @Then the backup file should exist in wp-content
	 */
	public function then_backup_file_should_exist_in_wp_content() {
		$run_dir = $this->variables['RUN_DIR'];
		$php     = 'echo implode( "\n", glob( WP_CONTENT_DIR . "/wcsr_backup_*.sql" ) );';

		$result = $this->proc( 'wp eval ' . escapeshellarg( $php ) )->run_check();
		$files  = array_filter( explode( "\n", trim( $result->stdout ) ) );

		if ( empty( $files ) ) {
			throw new \Exception( 'No wcsr_backup_*.sql file found in wp-content directory.' );
		}

		$this->variables['BACKUP_FILE'] = basename( $files[0] );
	}

	/**
	 * @Then the backup file should contain SQL for subscription :subscription_id
	 */
	public function then_backup_file_should_contain_sql_for_subscription( $subscription_id ) {
		$subscription_id = $this->replace_variables( $subscription_id );
		$run_dir         = $this->variables['RUN_DIR'];
		$backup_file     = $this->variables['BACKUP_FILE'];
		$file_path       = $run_dir . '/wp-content/' . $backup_file;

		if ( ! file_exists( $file_path ) ) {
			throw new \Exception( "Backup file not found: {$file_path}" );
		}

		$contents = file_get_contents( $file_path );

		if ( strpos( $contents, (string) $subscription_id ) === false ) {
			throw new \Exception(
				"Backup file does not contain data for subscription ID {$subscription_id}."
			);
		}
	}

	/**
	 * @Then the backup file should not exist in wp-content
	 */
	public function then_backup_file_should_not_exist_in_wp_content() {
		$backup_file = $this->variables['BACKUP_FILE'];
		$run_dir     = $this->variables['RUN_DIR'];
		$file_path   = $run_dir . '/wp-content/' . $backup_file;

		if ( file_exists( $file_path ) ) {
			throw new \Exception( "Backup file should have been deleted but still exists: {$file_path}" );
		}
	}
}
