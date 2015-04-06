<?php
/*
Plugin Name: Datafeedr WooCommerce Importer
Plugin URI: https://v4.datafeedr.com
Description: Import products from the Datafeedr Product Sets plugin into your WooCommerce store. <strong>REQUIRES: </strong><a href="http://wordpress.org/plugins/datafeedr-api/">Datafeedr API plugin</a>, <a href="http://wordpress.org/plugins/datafeedr-product-sets/">Datafeedr Product Sets plugin</a>, <a href="http://wordpress.org/plugins/woocommerce/">WooCommerce</a> (v2.1+).
Author: datafeedr.com
Author URI: https://v4.datafeedr.com
License: GPL v3
Requires at least: 3.8
Tested up to: 4.2-beta4
Version: 1.2.2

Datafeedr WooCommerce Importer plugin
Copyright (C) 2014, Datafeedr - eric@datafeedr.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define constants.
 */
define( 'DFRPSWC_VERSION', 		'1.2.2' );
define( 'DFRPSWC_DB_VERSION', 	'1.2.0' );
define( 'DFRPSWC_URL', 			plugin_dir_url( __FILE__ ) );
define( 'DFRPSWC_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'DFRPSWC_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'DFRPSWC_DOMAIN', 		'dfrpswc_integration' );
define( 'DFRPSWC_POST_TYPE', 	'product' );
define( 'DFRPSWC_TAXONOMY', 	'product_cat' );

/**
 * Load upgrade file.
 */
require_once( DFRPSWC_PATH . 'upgrade.php' );

/*******************************************************************
ADMIN NOTICES
*******************************************************************/

/**
 * Notify user that this plugin has been deactivated
 * because one of the plugins it requires is not active.
 */
add_action( 'admin_notices', 'dfrpswc_missing_required_plugins' );
function dfrpswc_missing_required_plugins() {
	
	if ( !defined( 'DFRPS_BASENAME' ) ) {
		echo '<div class="update-nag" style="border-color: red;">' . __( 'The <strong>Datafeedr WooCommerce Importer</strong> plugin requires that the <strong>Datafeedr Product Sets</strong> plugin be installed and activated.', DFRPSWC_DOMAIN );
		echo ' <a href="http://wordpress.org/plugins/datafeedr-product-sets/">';
		echo  __( 'Download the Datafeedr Product Sets Plugin', DFRPSWC_DOMAIN );
		echo '</a></div>';
	}
	
	if ( !class_exists( 'Woocommerce' ) ) {
		echo '<div class="update-nag" style="border-color: red;">' . __( 'The <strong>Datafeedr WooCommerce Importer</strong> plugin requires that the <strong>WooCommerce</strong> (v2.1+) plugin be installed and activated.', DFRPSWC_DOMAIN );
		echo ' <a href="http://wordpress.org/plugins/woocommerce/">';
		echo  __( 'Download the WooCommerce Plugin', DFRPSWC_DOMAIN );
		echo '</a></div>';
	}
}

/**
 * Display admin notices upon update.
 */
add_action( 'admin_notices', 'dfrpswc_settings_updated' );	
function dfrpswc_settings_updated() {
	if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true && isset( $_GET['page'] ) && 'dfrpswc_options' == $_GET['page'] ) {
		echo '<div class="updated">';
		_e( 'Configuration successfully updated!', DFRPSWC_DOMAIN );
		echo '</div>';
	}
}

/**
 * Notify user that their version of DFRPSWC is not compatible with their version of DFRPS.
 */
add_action( 'admin_notices', 'dfrpswc_not_compatible_with_dfrps' );
function dfrpswc_not_compatible_with_dfrps() {
	if ( defined( 'DFRPS_VERSION' ) )  {
		if ( version_compare( DFRPS_VERSION, '1.2.0', '<' ) ) {

			// Disable updates!
			$dfrps_configuration = get_option( 'dfrps_configuration' );
			$dfrps_configuration['updates_enabled'] = 'disabled';
			update_option( 'dfrps_configuration', $dfrps_configuration );

			$file = 'datafeedr-product-sets/datafeedr-product-sets.php';
			$url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file );

			?>
			
			<div class="error">
				<p>
					<strong style="color:#E44532;"><?php _e( 'URGENT - ACTION REQUIRED!', DFRPSWC_DOMAIN ); ?></strong>
					<br /><?php _e( 'Your version of the <strong><em>Datafeedr Product Sets</em></strong> plugin is not compatible with your version of the <strong><em>Datafeedr WooCommerce Importer</em></strong> plugin.', DFRPSWC_DOMAIN ); ?>
					<br /><?php _e( 'Failure to upgrade will result in data loss. Please update your version of the <strong><em>Datafeedr Product Sets</em></strong> plugin now.', DFRPSWC_DOMAIN ); ?>
					<br /><a class="button button-primary button-large" style="margin-top: 6px" href="<?php echo $url; ?>"><?php _e( 'Update Now', DFRPSWC_DOMAIN ); ?></a>
				</p>
			</div>
		
			<?php	
		}
	}
}

/*******************************************************************
REGISTER CUSTOM POST TYPE FOR PRODUCT SETS
*******************************************************************/

/**
 * This registers the third party integration's Custom
 * Post Type with the Datafeedr Product Sets plugin.
 */
add_action( 'init', 'dfrpswc_register_cpt' );
function dfrpswc_register_cpt() {
	if ( function_exists( 'dfrps_register_cpt' ) ) {
		$args = array(
			'taxonomy' 			=> DFRPSWC_TAXONOMY,
			'name' 				=> _x( 'WooCommerce Products', DFRPSWC_DOMAIN ),
			'tax_name'			=> _x( 'WooCommerce Categories', DFRPSWC_DOMAIN ),
			'tax_instructions' 	=> _x( 'Add this Product Set to a Product Category.', DFRPSWC_DOMAIN ),
		);
		dfrps_register_cpt( DFRPSWC_POST_TYPE, $args );
	}
}
	
/**
 * This unregisters the third party integration's Custom
 * Post Type from the Datafeedr Product Sets plugin. This
 * must be unregistered using the register_deactivation_hook()
 * hook.
 */
register_deactivation_hook( __FILE__, 'dfrpswc_unregister_cpt' );
function dfrpswc_unregister_cpt() {
	if ( function_exists( 'dfrps_unregister_cpt' ) ) {
		dfrps_unregister_cpt( DFRPSWC_POST_TYPE );
	}
}


/*******************************************************************
BUILD ADMIN OPTIONS PAGE
*******************************************************************/

/**
 * Add settings page.
 */
add_action( 'admin_menu', 'dfrpswc_admin_menu', 999 );	
function dfrpswc_admin_menu() {

	add_submenu_page(
		'dfrps',
		__( 'Options &#8212; Datafeedr WooCommerce Importer', DFRPSWC_DOMAIN ), 
		__( 'WC Importer', DFRPSWC_DOMAIN ), 
		'manage_options', 
		'dfrpswc_options',
		'dfrpswc_options_output'
	);
}

/**
 * Get current options or set default ones.
 */
function dfrpswc_get_options() {
	$options = get_option( 'dfrpswc_options', array() );
	if ( empty( $options ) ) {
		$options = array();
		$options['button_text'] = __( 'Buy Now', DFRPSWC_DOMAIN );
		update_option( 'dfrpswc_options', $options );
	}
	return $options;
}

/**
 * Build settings page.
 */
function dfrpswc_options_output() {
	echo '<div class="wrap" id="dfrpswc_options">';
	echo '<h2>' . __( 'Options &#8212; Datafeedr WooCommerce Importer', DFRPSWC_DOMAIN ) . '</h2>';
	echo '<form method="post" action="options.php">';
	wp_nonce_field( 'dfrpswc-update-options' );
	settings_fields( 'dfrpswc_options-page' );
	do_settings_sections( 'dfrpswc_options-page' );
	submit_button();
	echo '</form>';		
	echo '</div>';
}

/**
 * Register settings.
 */
add_action( 'admin_init', 'dfrpswc_register_settings' );
function dfrpswc_register_settings() {		
	register_setting( 'dfrpswc_options-page', 'dfrpswc_options', 'dfrpswc_validate' );
	add_settings_section( 'dfrpswc_general_settings', __( 'General Settings', DFRPSWC_DOMAIN ), 'dfrpswc_general_settings_section', 'dfrpswc_options-page' );
	add_settings_field( 'dfrpswc_button_text', __( 'Button Text', DFRPSWC_DOMAIN ), 'dfrpswc_button_text_field', 'dfrpswc_options-page', 'dfrpswc_general_settings' );
}

/**
 * General settings section description.
 */
function dfrpswc_general_settings_section() { 
	//echo __( 'General settings for importing products into your WooCommerce store.', DFRPSWC_DOMAIN );
}

/**
 * Button Text field.
 */
function dfrpswc_button_text_field() { 
	$options = dfrpswc_get_options();
	echo '<input type="text" class="regular-text" name="dfrpswc_options[button_text]" value="' . esc_attr( $options['button_text'] ) . '" />';
	echo '<p class="description">' . __( 'The text on the button which links to the merchant\'s website.', DFRPSWC_DOMAIN ) . '</p>';
}

/**
 * Validate user's input and save.
 */
function dfrpswc_validate( $input ) {
	if ( !isset( $input ) || !is_array( $input ) || empty( $input ) ) { return $input; }

	$new_input = array();
	foreach( $input as $key => $value ) {					
		if ( $key == 'button_text' ) {
			$new_input['button_text'] = trim( $value );
		}
	}
	return $new_input;
}

/**
 * Change Button Text for DFRPSWC imported products.
 */
add_filter( 'woocommerce_product_add_to_cart_text', 'dfrpswc_single_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'dfrpswc_single_add_to_cart_text', 10, 2 );
function dfrpswc_single_add_to_cart_text( $button_text, $type ) {
	global $product;
	if ( $type->product_type != 'external' ) { return $button_text; }
	if ( !dfrpswc_is_dfrpswc_product( $product->id ) ) { return $button_text; }
	$options = dfrpswc_get_options();
	if ( $options['button_text'] != '' ) {
		$button_text = $options['button_text'];
	}
	return $button_text;
}


/*******************************************************************
UPDATE FUNCTIONS
*******************************************************************/

/**
 * Return TRUE if finished unsetting categories.
 * Return FALSE if not finished.
 * 
 * This unsets products from their categories before updating products.
 * 
 * Why?
 * 
 * We need to remove all products which were imported via a product set
 * from the categories they were added to when they were imported 
 * so that at the end of the update, if these products weren't re-imported
 * during the update, the post/product's category information (for this
 * set) will no longer be available so that if this post/product was 
 * added via another Product Set, only that Product Set's category IDs
 * will be attributed to this post/product.
 * 
 * This processes batches at a time as this is a server/time
 * intensive process.
 */
add_action( 'dfrps_preprocess-' . DFRPSWC_POST_TYPE, 'dfrpswc_unset_post_categories' );
function dfrpswc_unset_post_categories( $obj ) {

	// Get posts to unset categories for.
	$posts = get_option( 'unset_post_categories_'.DFRPSWC_POST_TYPE.'_for_set_' . $obj->set['ID'] );
	
	/**
	 * If $posts does not exist (ie. FALSE) 
	 *    and
	 * If the the status of preprocess is FALSE
	 *    then
	 * Get all post IDs (as an array) set by this Product Set
	 * and set them to 'unset_post_categories_{product}_for_set_XXX'
	 * in the options table. That way we don't have to run the 
	 * dfrps_get_all_post_ids_by_set_id() query again and we can 
	 * incrementally remove post IDs from this array of IDs as we
	 * get ready for updating.
	 */
	if ( !$posts && !dfrpswc_process_complete( 'preprocess', $obj->set['ID'] ) ) {
		$posts = dfrps_get_all_post_ids_by_set_id( $obj->set['ID'] );
		update_option( 'unset_post_categories_'.DFRPSWC_POST_TYPE.'_for_set_' . $obj->set['ID'], $posts );
	}

	/**
	 * If $posts contains post IDs, we will grab the first X number of 
	 * IDs from the array (where X is "preprocess_maximum") and get all
	 * term_ids that the product is associated with from other Product Sets.
	 * 
	 * Then we will have an array of term_ids that this product belongs to 
	 * except the term_ids that this Product Set is responsible for adding.
	 * 
	 * Why?
	 * 
	 * Let's say we have the following situation:
	 * 
	 * SET A adds PRODUCT 1 to CATEGORY X
	 * SET B adds PRODUCT 1 to CATEGORY X
	 * 
	 * What happens when SET A removes PRODUCT 1 from CATEGORY X?
	 * 
	 * We need to make sure that PRODUCT 1 remains in CATEGORY X. By getting
	 * term_ids from all other Sets that added this product, we will keep
	 * PRODUCT 1 in CATEGORY X.
	 */
	if ( is_array( $posts ) && !empty( $posts ) ) {
		$config = (array) get_option( 'dfrps_configuration' );
		$ids = array_slice( $posts, 0, $config['preprocess_maximum'] );
		foreach ( $ids as $id ) {
			dfrps_add_term_ids_to_post( $id, $obj->set, DFRPSWC_POST_TYPE, DFRPSWC_TAXONOMY );
			delete_post_meta( $id, '_dfrps_product_set_id', $obj->set['ID'] );
			if ( ( $key = array_search( $id, $posts ) ) !== false ) {
				unset( $posts[$key] );
			}
		}
	}
		
	/**
	 * Now we need to check if there are more post IDs to process.
	 * 
	 * If $posts is empty, then we are done with the "preprocess" stage.
	 *    - Set "_dfrps_preprocess_complete_" to TRUE
	 *    - Delete the 'unset_post_categories_{product}_for_set_XXX' so 
	 *      no longer attempt to process it.
	 * 
	 * If $posts is NOT empty, we update 'unset_post_categories_{product}_for_set_XXX'
	 * with our reduced $posts array and let the "preprocess" run again.
	 */
	if ( empty( $posts ) ) {
		update_post_meta( $obj->set['ID'], '_dfrps_preprocess_complete_' . DFRPSWC_POST_TYPE, TRUE );
		delete_option( 'unset_post_categories_'.DFRPSWC_POST_TYPE.'_for_set_' . $obj->set['ID'] );
	} else {
		update_option( 'unset_post_categories_'.DFRPSWC_POST_TYPE.'_for_set_' . $obj->set['ID'], $posts );
	}
}

/**
 * Adds the action "dfrps_action_do_products_{cpt}" where
 * {cpt} is the post_type you are inserting products into.
 */
add_action( 'dfrps_action_do_products_' . DFRPSWC_POST_TYPE, 'dfrpswc_do_products', 10, 2 );
function dfrpswc_do_products( $data, $set ) {

	// Check if there are products available.
	if ( !isset( $data['products'] ) || empty( $data['products'] ) ) { return; }
	
	// Loop thru products.
	foreach ( $data['products'] as $product ) {
		
		// Get post if it already exists.
		$existing_post = dfrps_get_existing_post( $product, $set );

		// Determine what to do based on if post exists or not.
		if ( $existing_post && $existing_post['post_type'] == DFRPSWC_POST_TYPE ) {
			$action = 'update';
			$post = dfrpswc_update_post( $existing_post, $product, $set, $action );
		} else {
			$action = 'insert';
			$post = dfrpswc_insert_post( $product, $set, $action );
		}
		
		// Handle other facets for this product such as postmeta, terms and attributes.
		if ( $post ) {
			dfrpswc_update_terms( $post, $product, $set, $action );
			dfrpswc_update_postmeta( $post, $product, $set, $action );
			dfrpswc_update_attributes( $post, $product, $set, $action );
			do_action( 'dfrpswc_do_product', $post, $product, $set, $action );
		}		
	}
}

/**
 * This updates a post.
 * 
 * This should return a FULL $post object in ARRAY_A format.
 */
function dfrpswc_update_post( $existing_post, $product, $set, $action ) {

	$post = array(
		'ID' 			=> $existing_post['ID'],
		'post_title' 	=> @$product['name'],
		'post_content' 	=> @$product['description'],
		'post_excerpt' 	=> @$product['shortdescription'],
	  	'post_status'   => 'publish',
	);
	
	// Apply any custom filters.
	$post = apply_filters( 'dfrpswc_filter_post_array', $post, $product, $set, $action );
	wp_update_post( $post );
	return $post;
}

/**
 * This inserts a new post.
 *
 * This should return a FULL $post object in ARRAY_A format.
 */
function dfrpswc_insert_post( $product, $set, $action ) {
	
	$post = array(
		'post_title'    => @$product['name'],
		'post_content'  => @$product['description'],
		'post_excerpt' 	=> @$product['shortdescription'],
		'post_status'   => 'publish',
		'post_author'   => $set['post_author'],
		'post_type'	  	=> DFRPSWC_POST_TYPE,
	);

	// Apply any custom filters.
	$post = apply_filters( 'dfrpswc_filter_post_array', $post, $product, $set, $action );
	$id = wp_insert_post( $post );		
	$post['ID'] = $id;
	return $post;
}

/**
 * Update the postmeta for this product.
 */
function dfrpswc_update_postmeta( $post, $product, $set, $action ) {

	$meta = array();
	
	$meta['_visibility'] 				= 'visible';
	$meta['_stock'] 					= '';
	$meta['_downloadable'] 				= 'no';
	$meta['_virtual'] 					= 'no';
	$meta['_backorders'] 				= 'no';
	$meta['_stock_status'] 				= 'instock';
	$meta['_product_type'] 				= 'external';
	$meta['_product_url'] 				= $product['url'];
	$meta['_sku'] 						= $product['_id'];
	$meta['_dfrps_is_dfrps_product'] 	= true;
	$meta['_dfrps_is_dfrpswc_product'] 	= true;
	$meta['_dfrps_product_id'] 			= $product['_id'];
	$meta['_dfrps_product'] 			= $product; // This stores all info about the product in 1 array.

	// Update image check field.
	$meta['_dfrps_product_check_image'] = 1;
	
	// Set featured image url (if there's an image)
	if ( @$product['image'] != '' ) {
		$meta['_dfrps_featured_image_url'] = @$product['image'];
	} elseif ( @$product['thumbnail'] != '' ) {
		$meta['_dfrps_featured_image_url'] = @$product['thumbnail'];
	}
	
	// Handle price.
	if ( isset( $product['price'] ) ) {
		$meta['_regular_price'] = dfrps_int_to_price( $product['price'] );
		$meta['_price'] = dfrps_int_to_price( $product['price'] );
	}
	
	// Handle sale price.
	if ( isset( $product['saleprice'] ) ) {
		$meta['_sale_price'] = dfrps_int_to_price( $product['saleprice'] );
		$meta['_price'] = dfrps_int_to_price( $product['saleprice'] );
	}
	
	// Handle sale discount.
	$meta['_dfrps_salediscount'] = ( isset( $product['salediscount'] ) ) ? $product['salediscount'] : 0;
	
	$meta = apply_filters( 'dfrpswc_filter_postmeta_array', $meta, $post, $product, $set, $action );
	
	$dont_recheck_image = false;
	foreach ( $meta as $meta_key => $meta_value ) {
		update_post_meta( $post['ID'], $meta_key, $meta_value );
	}
	
	add_post_meta( $post['ID'], '_dfrps_product_set_id', $set['ID'] );
}

/**
 * Update the terms/taxonomy for this product.
 */
function dfrpswc_update_terms( $post, $product, $set, $action ) {
	
	// Get the IDs of the categories this product is associated with.
	$terms = array();
	$terms = dfrps_get_cpt_terms( $set['ID'] );
	
	// Create an array with key of taxonomy and values of terms
	$taxonomies = array(
		DFRPSWC_TAXONOMY	=> $terms,
		'product_tag' 		=> '',
		'product_type' 		=> 'external',
	);

	// Then apply filters so users can override
	$taxonomies = apply_filters( 'dfrpswc_filter_taxonomy_array', $taxonomies, $post, $product, $set, $action );
	
	// Remove 'product_tag' from array if value is empty.
	if ( empty( $taxonomies['product_tag'] ) ) {
		unset( $taxonomies['product_tag'] );
	}
	
	// Then iterate over the array using wp_set_post_terms()
	foreach ( $taxonomies as $taxonomy => $terms ) {
		$append = ( $taxonomy == DFRPSWC_TAXONOMY ) ? TRUE : FALSE;
		$result = wp_set_post_terms( $post['ID'], $terms, $taxonomy, $append );
	}
}

/**
 * Update the attributes (unique to WC) for this product.
 * Most code from:
 * ~/wp-content/plugins/woocommerce/includes/admin/post-types/meta-boxes/class-wc-meta-box-product-data.php (Line #397)
 */
function dfrpswc_update_attributes( $post, $product, $set, $action ) {
	
	$attrs = array();
	
	// Array of defined attribute taxonomies
	$attribute_taxonomies = wc_get_attribute_taxonomies();
	
	// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
	$attributes = maybe_unserialize( get_post_meta( $post['ID'], '_product_attributes', true ) );
	$attributes = apply_filters( 'dfrpswc_product_attributes', $attributes, $post, $product, $set, $action );
	
	$i = -1;

	// Taxonomies (attributes)
	if ( $attribute_taxonomies ) {
				
		foreach ( $attribute_taxonomies as $tax ) {

			// Get name of taxonomy we're now outputting (pa_xxx)
			$attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );

			// Ensure it exists
			if ( ! taxonomy_exists( $attribute_taxonomy_name ) ) {
				continue;
			}
			
			$i++;

			// Get product data values for current taxonomy - this contains ordering and visibility data
			if ( isset( $attributes[ sanitize_title( $attribute_taxonomy_name ) ] ) ) {
				$attribute = $attributes[ sanitize_title( $attribute_taxonomy_name ) ];
			}

			$position = empty( $attribute['position'] ) ? 0 : absint( $attribute['position'] );
			$visibility = 1;
			$variation = 0;

			// Get terms of this taxonomy associated with current product
			$post_terms = wp_get_post_terms( $post['ID'], $attribute_taxonomy_name );			
			
			if ( $post_terms ) {
				$value = array();
				foreach ( $post_terms as $term ) {
					$value[] = $term->slug;
				}
			} else {
				$value = '';
			}
			
			$attrs['attribute_names'][$i] 		= $attribute_taxonomy_name;
			$attrs['attribute_is_taxonomy'][$i] = 1;
			$attrs['attribute_values'][$i] 		= apply_filters( 'dfrpswc_filter_attribute_value', $value, $attribute_taxonomy_name, $post, $product, $set, $action );
			$attrs['attribute_position'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_position', $position, $attribute_taxonomy_name, $post, $product, $set, $action );
			$attrs['attribute_visibility'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_visibility', $visibility, $attribute_taxonomy_name, $post, $product, $set, $action );
			$attrs['attribute_variation'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_variation', $variation, $attribute_taxonomy_name, $post, $product, $set, $action );
	
		} // foreach ( $attribute_taxonomies as $tax ) {
		
	} // if ( $attribute_taxonomies ) {
	
	// Custom Attributes
	if ( ! empty( $attributes ) ) {
		
		foreach ( $attributes as $attribute ) {
		
			if ( isset( $attribute['is_taxonomy'] ) ) {
				continue;
			}

			$i++;
						
			$attribute_name = $attribute['name'];
			
			$position = empty( $attribute['position'] ) ? 0 : absint( $attribute['position'] );
			$visibility = 1;
			$variation = 0;

			// Get value.
			$value = ( isset( $attribute['value'] ) ) ? $attribute['value'] : '';
						
			$attrs['attribute_names'][$i] 		= $attribute_name;
			$attrs['attribute_is_taxonomy'][$i] = 0;
			$attrs['attribute_values'][$i] 		= apply_filters( 'dfrpswc_filter_attribute_value', $value, $attribute_name, $post, $product, $set, $action );
			$attrs['attribute_position'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_position', $position, $attribute_name, $post, $product, $set, $action );
			$attrs['attribute_visibility'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_visibility', $visibility, $attribute_name, $post, $product, $set, $action );
			$attrs['attribute_variation'][$i] 	= apply_filters( 'dfrpswc_filter_attribute_variation', $variation, $attribute_name, $post, $product, $set, $action );
		
		} // foreach ( $attributes as $attribute ) {
 		
	} // if ( ! empty( $attributes ) ) {
	
	$attrs = apply_filters( 'dfrpswc_pre_save_attributes', $attrs, $post, $product, $set, $action );
	
	// Save Attributes
	dfrpswc_save_attributes( $post['ID'], $attrs );
}

/**
 * Add network attribute.
 */
add_filter( 'dfrpswc_filter_attribute_value', 'dfrpswc_add_network_attribute', 10, 6 );
function dfrpswc_add_network_attribute( $value, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_network') {
		$value = $product['source'];
	}
	return $value;
}

/**
 * Set "position" of network attribute.
 */
add_filter( 'dfrpswc_filter_attribute_position', 'dfrpswc_set_network_attribute_position', 10, 6 );
function dfrpswc_set_network_attribute_position( $position, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_network') {
		$position = 1;
	}
	return $position;
}

/**
 * Set "visibility" of network attribute to hidden (0).
 */
add_filter( 'dfrpswc_filter_attribute_visibility', 'dfrpswc_hide_network_attribute', 10, 6 );
function dfrpswc_hide_network_attribute( $visibility, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_network') {
		$visibility = 0;
	}
	return $visibility;
}

/**
 * Add merchant attribute.
 */
add_filter( 'dfrpswc_filter_attribute_value', 'dfrpswc_add_merchant_attribute', 10, 6 );
function dfrpswc_add_merchant_attribute( $value, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_merchant') {
		$value = $product['merchant'];
	}
	return $value;
}

/**
 * Set "position" of merchant attribute.
 */
add_filter( 'dfrpswc_filter_attribute_position', 'dfrpswc_set_merchant_attribute_position', 10, 6 );
function dfrpswc_set_merchant_attribute_position( $position, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_merchant') {
		$position = 2;
	}
	return $position;
}

/**
 * Add brand attribute.
 */
add_filter( 'dfrpswc_filter_attribute_value', 'dfrpswc_add_brand_attribute', 10, 6 );
function dfrpswc_add_brand_attribute( $value, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_brand') {
		if ( isset( $product['brand'] ) ) {
			$value = $product['brand'];
		}
	}
	return $value;
}

/**
 * Set "position" of brand attribute.
 */
add_filter( 'dfrpswc_filter_attribute_position', 'dfrpswc_set_brand_attribute_position', 10, 6 );
function dfrpswc_set_brand_attribute_position( $position, $attribute, $post, $product, $set, $action ) {
	if ( $attribute == 'pa_brand') {
		$position = 3;
	}
	return $position;
}


/**
 * This saves WC attribute data. 
 *
 * Most code comes from Line #1000 here: 
 * ~/wp-content/plugins/woocommerce/includes/admin/post-types/meta-boxes/class-wc-meta-box-product-data.php
 */
function dfrpswc_save_attributes( $post_id, $dfrpswc_attributes ) {

	// Save Attributes
	$attributes = array();
	
	if ( isset( $dfrpswc_attributes['attribute_names'] ) && isset( $dfrpswc_attributes['attribute_values'] ) ) {
		
		$attribute_names  = $dfrpswc_attributes['attribute_names'];
		$attribute_values = $dfrpswc_attributes['attribute_values'];

		if ( isset( $dfrpswc_attributes['attribute_visibility'] ) )
			$attribute_visibility = $dfrpswc_attributes['attribute_visibility'];

		if ( isset( $dfrpswc_attributes['attribute_variation'] ) )
			$attribute_variation = $dfrpswc_attributes['attribute_variation'];

		$attribute_is_taxonomy = $dfrpswc_attributes['attribute_is_taxonomy'];
		$attribute_position = $dfrpswc_attributes['attribute_position'];

		$attribute_names_count = sizeof( $attribute_names );

		for ( $i=0; $i < $attribute_names_count; $i++ ) {
			
			if ( ! $attribute_names[ $i ] ) {
				continue;
			}

			$is_visible 	= ( isset( $attribute_visibility[ $i ] ) && $attribute_visibility[ $i ] != 0 ) ? 1 : 0;
			$is_variation 	= ( isset( $attribute_variation[ $i ] ) && $attribute_variation[ $i ] != 0 ) ? 1 : 0;
			$is_taxonomy 	= $attribute_is_taxonomy[ $i ] ? 1 : 0;
			
			if ( $is_taxonomy ) {

				if ( isset( $attribute_values[ $i ] ) ) {

					// Select based attributes - Format values (posted values are slugs)
					if ( is_array( $attribute_values[ $i ] ) ) {
						$values = array_map( 'sanitize_title', $attribute_values[ $i ] );

					// Text based attributes - Posted values are term names - don't change to slugs
					} else {
						$values = array_map( 'stripslashes', array_map( 'strip_tags', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) );
					}

					// Remove empty items in the array
					$values = array_filter( $values, 'strlen' );

				} else {
				
					$values = array();
				}

				// Update post terms
				if ( taxonomy_exists( $attribute_names[ $i ] ) ) {
					wp_set_object_terms( $post_id, $values, $attribute_names[ $i ] );
				}

				if ( $values ) {
					// Add attribute to array, but don't set values
					$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
						'name' 			=> wc_clean( $attribute_names[ $i ] ),
						'value' 		=> '',
						'position' 		=> $attribute_position[ $i ],
						'is_visible' 	=> $is_visible,
						'is_variation' 	=> $is_variation,
						'is_taxonomy' 	=> $is_taxonomy
					);
				}

			} elseif ( isset( $attribute_values[ $i ] ) ) {

				// Text based, separate by pipe
				$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'wc_clean', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) );

				// Custom attribute - Add attribute to array and set the values
				$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
					'name' 			=> wc_clean( $attribute_names[ $i ] ),
					'value' 		=> $values,
					'position' 		=> $attribute_position[ $i ],
					'is_visible' 	=> $is_visible,
					'is_variation' 	=> $is_variation,
					'is_taxonomy' 	=> $is_taxonomy
				);
			}

		 }
	}

	if ( ! function_exists( 'attributes_cmp' ) ) {
		function attributes_cmp( $a, $b ) {
			if ( $a['position'] == $b['position'] ) return 0;
			return ( $a['position'] < $b['position'] ) ? -1 : 1;
		}
	}
	
	uasort( $attributes, 'attributes_cmp' );

	update_post_meta( $post_id, '_product_attributes', $attributes );
}

/**
 * This is clean up after the update is finished.  
 * Here we will:
 * 
 * Delete (move to Trash) all products which were "stranded" after the update.
 * Strandad means they no longer have a Product Set ID associated with them.
 */
add_action( 'dfrps_postprocess-' . DFRPSWC_POST_TYPE, 'dfrpswc_delete_stranded_products' );
function dfrpswc_delete_stranded_products( $obj ) {

	$config = (array) get_option( 'dfrps_configuration' );

	// Should we even delete missing products?
	if ( isset( $config['delete_missing_products'] ) && ( $config['delete_missing_products'] == 'no' ) ) {
		update_post_meta( $obj->set['ID'], '_dfrps_postprocess_complete_' . DFRPSWC_POST_TYPE, true );

		return;
	}

	// If trashable posts are already set for this Set['ID'], then just return.
	$trashable_posts = get_option( 'trashable_posts_for_set_' . $obj->set['ID'] );

	// If we've not run the SQL to get trashable posts, do so now.
	if ( ! $trashable_posts && ! dfrpswc_process_complete( 'postprocess', $obj->set['ID'] ) ) {
	
		global $wpdb;
	
		$posts = $wpdb->get_results( "
			SELECT pm.post_id
			FROM $wpdb->postmeta pm
			LEFT JOIN $wpdb->postmeta pm1
				ON pm.post_id = pm1.post_id 
					AND pm1.meta_key = '_dfrps_product_set_id'
			JOIN $wpdb->posts p
				ON pm.post_id = p.ID
			WHERE
				pm.meta_key = '_dfrps_is_dfrps_product'
				AND pm.meta_value = 1
				AND pm1.post_id IS NULL
				AND p.post_status = 'publish'
		", ARRAY_A );
	
		$post_ids = array();
		foreach ( $posts as $post ) {
			$post_ids[] = $post['post_id'];
		}
				
		if ( empty( $post_ids ) ) {
			update_post_meta( $obj->set['ID'], '_dfrps_postprocess_complete_' . DFRPSWC_POST_TYPE, true );
			update_post_meta( $obj->set['ID'], '_dfrps_cpt_last_update_num_products_deleted', count( $post_ids ) );
			return;
		}

		$trashable_posts = $post_ids;
		add_option( 'trashable_posts_for_set_' . $obj->set['ID'], $post_ids, '', 'no' );
		update_post_meta( $obj->set['ID'], '_dfrps_cpt_last_update_num_products_deleted', count( $post_ids ) );
	}

	if ( is_array( $trashable_posts ) && ! empty( $trashable_posts ) ) {

		/**
		 * The function to pass the post ID to when it is no longer in the store (ie. deleted, trashed).
		 *
		 * Default is wp_trash_post().
		 *
		 * We use a filter here instead of an action because if a do_action was used within the foreach()
		 * then the post (ie. $id) could possibly be put through multiple actions, causing too much unnecessary load
		 * during an already intense process.
		 * By applying a filter to the function name, we guarantee that the $id will only be passed
		 * through to one function. Also, we don't make multiple calls to apply_filters() or do_action()
		 * from within the foreach() loop. Keep it outside of the loop to prevent more than one
		 * call to apply_filters().
		 */
		$func = apply_filters( 'dfrpswc_process_stranded_product', 'wp_trash_post' );

		$ids = array_slice( $trashable_posts, 0, $config['postprocess_maximum'] );
		foreach ( $ids as $id ) {
			$func( $id );
			if ( ( $key = array_search( $id, $trashable_posts ) ) !== false ) {
				unset( $trashable_posts[ $key ] );
			}
		}
	}
	
	if ( empty( $trashable_posts ) ) {
		delete_option( 'trashable_posts_for_set_' . $obj->set['ID'] );		
		update_post_meta( $obj->set['ID'], '_dfrps_postprocess_complete_' . DFRPSWC_POST_TYPE, true );		
		return;
	} else {
		update_option( 'trashable_posts_for_set_' . $obj->set['ID'], $trashable_posts );
		return;
	}
}

/**
 * When update is complete, Recount Terms.
 * 
 * This code is taken from public function status_tools()
 * in ~/wp-content/plugins/woocommerce/includes/admin/class-wc-admin-status.php
 */
add_action( 'dfrps_set_update_complete', 'dfrpswc_update_complete' );
function dfrpswc_update_complete( $set ) {		
	
	$product_cats = get_terms( DFRPSWC_TAXONOMY, array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
	_wc_term_recount( $product_cats, get_taxonomy( DFRPSWC_TAXONOMY ), true, false );

	$product_tags = get_terms( DFRPSWC_TAXONOMY, array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
	_wc_term_recount( $product_tags, get_taxonomy( DFRPSWC_TAXONOMY ), true, false );

	delete_transient( 'wc_term_counts' );
} 


/*******************************************************************
INSERT AFFILIATE ID INTO AFFILIATE LINK
*******************************************************************/

/**
 * Extend "WC_Product_External" class.
 * This tells WC to use the "Dfrpswc_Product_External" class if 
 * a product is an external product.
 * 
 * This returns the default class if the WooCommerce Cloak Affiliate Links 
 * plugin is activated.
 */
add_filter( 'woocommerce_product_class', 'dfrpswc_woocommerce_product_class', 40, 4 );
function dfrpswc_woocommerce_product_class( $classname, $product_type, $post_type, $product_id ) {
	if ( $classname != 'WC_Product_External' ) 			{ return $classname; }
	if ( class_exists( 'Wccal' ) ) 						{ return $classname; }
	if ( !dfrpswc_is_dfrpswc_product( $product_id ) ) 	{ return $classname; }	
	return 'Dfrpswc_Product_External';
}

/**
 * Creates the "Dfrpswc_Product_External" class in order to modify 
 * the product_url() method.
 * 
 * The product_url() method returns the affiliate link with the affiliate
 * id inserted.
 * 
 * This does nothing if the WooCommerce Cloak Affiliate Links 
 * plugin is activated.
 */
add_action( 'plugins_loaded', 'dfrpswc_extend_wc_product_external_class' );
function dfrpswc_extend_wc_product_external_class() {
	if ( class_exists( 'WC_Product_External' ) && !class_exists( 'Wccal' ) ) {	
		class Dfrpswc_Product_External extends WC_Product_External {
			public function get_product_url() {
				if ( dfrpswc_is_dfrpswc_product( $this->id ) ) {
					$product = get_post_meta( $this->id, '_dfrps_product', true );
					$external_link = dfrapi_url( $product );
					if ( $external_link != '' ) { 
						$url = $external_link;
					} else {
						$url = get_permalink( $this->id );
					}					
					return $url;
				}
			}
		}
	}
}

/**
 * This returns the affiliate link with affiliate ID inserted 
 * if the WooCommerce Cloak Affiliate Links plugin is activated.
 */
add_filter( 'wccal_filter_url', 'dfrpswc_add_affiliate_id_to_url', 20, 2 );
function dfrpswc_add_affiliate_id_to_url( $external_link, $post_id ) {
	if ( dfrpswc_is_dfrpswc_product( $post_id ) ) {
		$product = get_post_meta( $post_id, '_dfrps_product', true );
		$external_link = dfrapi_url( $product );
	}
	return $external_link;
}


/*******************************************************************
ADD METABOX TO PRODUCT'S EDIT PAGE.
*******************************************************************/

/**
 * Add meta box to WC product pages so that a user can
 * see which product sets added this product.
 */
add_action( 'admin_menu', 'dfrpswc_add_meta_box' );
function dfrpswc_add_meta_box() {
	add_meta_box(
		'dfrpswc_product_sets_relationships', 
		_x( 'Datafeedr Product Sets', DFRPSWC_DOMAIN ), 
		'dfrpswc_product_sets_relationships_metabox', 
		DFRPSWC_POST_TYPE, 
		'side', 
		'low', 
		array()
	);
}

/**
 * The metabox content.
 */
function dfrpswc_product_sets_relationships_metabox( $post, $box ) {
	$set_ids = get_post_meta( $post->ID, '_dfrps_product_set_id', false );
	$set_ids = array_unique( $set_ids );
	if ( !empty( $set_ids ) ) {
		echo '<p>' . __( 'This product was added by the following Product Set(s)', DFRPSWC_DOMAIN ) . '</p>';
		foreach ( $set_ids as $set_id ) {
			echo '<div><a href="' . get_edit_post_link( $set_id ) . '" title="' . __( 'View this Product Set', DFRPSWC_DOMAIN ) . '">' . get_the_title( $set_id ) . '</a></div>';
		}
	} else {
		echo '<p>' . __( 'This product was not added by a Datafeedr Product Set.', DFRPSWC_DOMAIN ) . '</p>';
	}
}


/*******************************************************************
MISCELLANEOUS FUNCTIONS
*******************************************************************/

/**
 * Returns true if product was imported by this plugin (Datafeedr WooCommerce Importer)
 */
function dfrpswc_is_dfrpswc_product( $product_id ) {
	if ( get_post_meta( $product_id, '_dfrps_is_dfrpswc_product', true ) != '' ) {
		return true;
	}
	return false;
}

/**
 * A helper function which allows a user to add additional WooCommerce
 * attributes to their product.
 */
function dfrpswc_add_attribute( $product, $attributes, $field, $taxonomy, $is_taxonomy, $position=1, $is_visible=1, $is_variation=0 ) {
	if ( isset( $product[$field] ) && ( $product[$field] != '' ) ) {
		$attributes[$taxonomy] = array(
			'name' 			=> $taxonomy,
			'value' 		=> $product[$field],
			'position' 		=> $position,
			'is_visible' 	=> $is_visible,
			'is_variation' 	=> $is_variation,
			'is_taxonomy' 	=> $is_taxonomy,
			'field'			=> $field,
		);
	}
	return $attributes;
}

/**
 * A helper function to determine if either the preprocess or postprocess
 * processes are complete.  
 * 
 * Returns true if complete, false if not complete.
 */
function dfrpswc_process_complete( $process, $set_id ) {
	$status = get_post_meta( $set_id, '_dfrps_' . $process . '_complete_' . DFRPSWC_POST_TYPE, true );
	if ( $status == '' ) {
		return false;
	}
	return true;
}

/**
 * Add extra links to plugin page.
 */
add_filter( 'plugin_row_meta', 'dfrpswc_plugin_row_meta', 10, 2 );
function dfrpswc_plugin_row_meta( $links, $plugin_file ) {
	if ( $plugin_file == DFRPSWC_BASENAME ) {
		$links[] = sprintf( '<a href="' . DFRAPI_HELP_URL . '">%s</a>', __( 'Support', DFRPSWC_DOMAIN ) );
		return $links;
	}
	return $links;
}

/**
 * Links to other related or required plugins.
 */
function dfrpswc_plugin_links( $plugin ) {
	$map = array(
		'dfrapi' => 'http://wordpress.org/plugins/datafeedr-api/',
		'dfrps' => 'http://wordpress.org/plugins/datafeedr-product-sets/',
		'woocommerce' => 'http://wordpress.org/plugins/woocommerce/',
		//'importers' => admin_url( 'plugin-install.php?tab=search&type=term&s=dfrps_importer&plugin-search-input=Search+Plugins' ),
		'importers' => admin_url( 'plugins.php' ),
	);
	return $map[$plugin];
}

add_filter( 'plugin_action_links_' . DFRPSWC_BASENAME, 'dfrpswc_action_links' );
function dfrpswc_action_links( $links ) {
	return array_merge(
		$links,
		array(
			'config' => '<a href="' . admin_url( 'admin.php?page=dfrpswc_options' ) . '">' . __( 'Configuration', DFRPSWC_DOMAIN ) . '</a>',
		)
	);
}

/**
 * When a term is split, ensure postmeta for Product Set is maintained.
 *
 * Whenever a term is edited, this function loops through all postmeta values of '_dfrps_cpt_terms' and looks for any
 * old term_ids. If they exist, the '_dfrps_cpt_terms' is updated with the new term_id.
 *
 * This only happens when a shared term is updated (eg, when its name is updated in the Dashboard).
 *
 * @since 1.2.1
 *
 * @link https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/
 *
 * @param  int    $old_term_id The old term ID to search for.
 * @param  int    $new_term_id The new term ID to replace the old one with.
 * @param  int    $term_obj_taxonomy_id The term's tax ID.
 * @param  string $taxonomy The corresponding taxonomy.
 */
add_action( 'split_shared_term', 'dfrpswc_update_terms_for_split_terms', 20, 4 );
function dfrpswc_update_terms_for_split_terms( $old_term_id, $new_term_id, $term_obj_taxonomy_id, $taxonomy ) {

	if ( $taxonomy !== 'product_cat' ) {
		return true;
	}

	global $wpdb;

	$current_cpt_terms = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_dfrps_cpt_terms'" );

	if ( empty( $current_cpt_terms ) ) {
		return true;
	}

	foreach ( $current_cpt_terms as $item => $term_obj ) {

		$current_meta_value = maybe_unserialize( $term_obj->meta_value );

		if ( in_array( $old_term_id, $current_meta_value ) ) {

			// @link http://stackoverflow.com/a/8668861
			$new_meta_value = array_replace(
				$current_meta_value,
				array_fill_keys(
					array_keys( $current_meta_value, $old_term_id ),
					$new_term_id
				)
			);

			update_post_meta( $term_obj->post_id, '_dfrps_cpt_terms', $new_meta_value );

		}
	}
}
