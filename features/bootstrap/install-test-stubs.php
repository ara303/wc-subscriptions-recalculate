<?php

$stub_plugin = <<<'PHP'
<?php

class WC_Subscriptions {}

class WC_Tax {
	public static function get_rates( $tax_class ) {
		return [];
	}

	public static function calc_tax( $price, $tax_rates, $prices_include_tax ) {
		return [];
	}
}

function wc_prices_include_tax() {
	return false;
}

function wc_get_product( $product_id ) {
	$product = get_post( $product_id );

	if ( ! $product || 'product' !== $product->post_type ) {
		return false;
	}

	return new WCSR_Test_Product( $product_id );
}

function wcs_get_subscription( $subscription_id ) {
	$subscription = get_post( $subscription_id );

	if ( ! $subscription || 'shop_subscription' !== $subscription->post_type ) {
		return false;
	}

	return new WCSR_Test_Subscription( $subscription_id );
}

function wcs_get_subscriptions( $args = [] ) {
	$status     = $args['subscription_status'] ?? 'any';
	$meta_query = [];

	if ( 'any' !== $status ) {
		$meta_query[] = [
			'key'   => '_wcsr_status',
			'value' => $status,
		];
	}

	$posts = get_posts(
		[
			'post_type'      => 'shop_subscription',
			'post_status'    => 'any',
			'posts_per_page' => $args['subscriptions_per_page'] ?? -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => $meta_query,
		]
	);

	$subscriptions = [];

	foreach ( $posts as $post ) {
		$subscriptions[] = new WCSR_Test_Subscription( $post->ID );
	}

	return $subscriptions;
}

class WCSR_Test_Product {
	private $id;

	public function __construct( $id ) {
		$this->id = (int) $id;
	}

	public function get_price() {
		return (string) get_post_meta( $this->id, '_price', true );
	}

	public function get_tax_class() {
		return '';
	}
}

class WCSR_Test_Subscription {
	private $id;

	public function __construct( $id ) {
		$this->id = (int) $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_items() {
		$items        = (array) get_post_meta( $this->id, '_wcsr_items', true );
		$return_items = [];

		foreach ( $items as $index => $item ) {
			$return_items[] = new WCSR_Test_Subscription_Item( $this->id, $index, $item );
		}

		return $return_items;
	}

	public function set_total( $total ) {
		update_post_meta( $this->id, '_order_total', (string) $total );
	}

	public function calculate_taxes() {}

	public function save() {}
}

class WCSR_Test_Subscription_Item {
	private $subscription_id;
	private $index;
	private $data;

	public function __construct( $subscription_id, $index, $data ) {
		$this->subscription_id = (int) $subscription_id;
		$this->index           = (int) $index;
		$this->data            = (array) $data;
	}

	public function get_product_id() {
		return (int) $this->data['product_id'];
	}

	public function get_subtotal() {
		return (string) $this->data['subtotal'];
	}

	public function set_taxes( $taxes ) {
		$this->data['taxes'] = $taxes;
	}

	public function set_subtotal( $subtotal ) {
		$this->data['subtotal'] = (string) $subtotal;
	}

	public function set_total( $total ) {
		$this->data['total'] = (string) $total;
	}

	public function save() {
		$items                = (array) get_post_meta( $this->subscription_id, '_wcsr_items', true );
		$items[ $this->index ] = $this->data;

		update_post_meta( $this->subscription_id, '_wcsr_items', $items );
	}
}
PHP;

if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
	wp_mkdir_p( WPMU_PLUGIN_DIR );
}

file_put_contents( WPMU_PLUGIN_DIR . '/wcsr-test-stubs.php', $stub_plugin );

echo 'Installed test stubs.';
