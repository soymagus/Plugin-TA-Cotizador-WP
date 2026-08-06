<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Las solicitudes se conservan deliberadamente como registros comerciales.
delete_option( 'taql_page_id' );

