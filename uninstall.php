<?php
/**
 * Uninstall MPRO Text Canvas.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mpro_tc_settings' );

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'mpro_tc_settings' );
		restore_current_blog();
	}
}
