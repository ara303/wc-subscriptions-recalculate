<?php

$active_product_id = wp_insert_post(
	[
		'post_title'  => 'Updated price product',
		'post_type'   => 'product',
		'post_status' => 'publish',
	]
);

update_post_meta( $active_product_id, '_price', '25' );

$cancelled_product_id = wp_insert_post(
	[
		'post_title'  => 'Unchanged product',
		'post_type'   => 'product',
		'post_status' => 'publish',
	]
);

update_post_meta( $cancelled_product_id, '_price', '18' );

$active_subscription_id = wp_insert_post(
	[
		'post_title'  => 'Active test subscription',
		'post_type'   => 'shop_subscription',
		'post_status' => 'publish',
	]
);

update_post_meta( $active_subscription_id, '_wcsr_status', 'active' );
update_post_meta(
	$active_subscription_id,
	'_wcsr_items',
	[
		[
			'product_id' => $active_product_id,
			'subtotal'   => '15',
			'total'      => '15',
		],
	]
);

$cancelled_subscription_id = wp_insert_post(
	[
		'post_title'  => 'Cancelled test subscription',
		'post_type'   => 'shop_subscription',
		'post_status' => 'publish',
	]
);

update_post_meta( $cancelled_subscription_id, '_wcsr_status', 'cancelled' );
update_post_meta(
	$cancelled_subscription_id,
	'_wcsr_items',
	[
		[
			'product_id' => $cancelled_product_id,
			'subtotal'   => '8',
			'total'      => '8',
		],
	]
);

update_option( 'wcsr_test_active_subscription_id', $active_subscription_id );
update_option( 'wcsr_test_cancelled_subscription_id', $cancelled_subscription_id );

echo 'Seeded update command test data.';
