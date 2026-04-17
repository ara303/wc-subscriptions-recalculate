<?php

namespace WP_CLI\WCSRecalculate;

use WP_CLI;

if ( ! class_exists( '\WP_CLI' ) ) {
	return;
}

$wcsr_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wcsr_autoloader ) ) {
	require_once $wcsr_autoloader;
}

WP_CLI::add_command( 'wcsr', Command::class );
