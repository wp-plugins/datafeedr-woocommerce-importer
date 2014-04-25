<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * BEGIN
 * 
 * Get previous version stored in database.
 */
$previous_version = get_option( 'dfrpswc_version', FALSE );


/**
 * Upgrade functions go here...
 */



/**
 * END
 * 
 * Now that any upgrade functions are performed, update version in database.
 */
add_option( 'dfrpswc_version', DFRPSWC_VERSION );
