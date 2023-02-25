<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dngstudio.co
 * @since             1.0.0
 * @package           Easy_Shortcodes
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Shortcodes
 * Plugin URI:        https://dngstudio.co/products/easy-shortcodes
 * Description:       Create shortcodes and assign values to them in just a few simple steps.
 * Version:           1.0.0
 * Author:            DNG Studio
 * Author URI:        https://dngstudio.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       easy-shortcodes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EASY_SHORTCODES_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-easy-shortcodes-activator.php
 */
function activate_easy_shortcodes() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-shortcodes-activator.php';
	Easy_Shortcodes_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-easy-shortcodes-deactivator.php
 */
function deactivate_easy_shortcodes() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-easy-shortcodes-deactivator.php';
	Easy_Shortcodes_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_easy_shortcodes' );
register_deactivation_hook( __FILE__, 'deactivate_easy_shortcodes' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-easy-shortcodes.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_easy_shortcodes() {

	$plugin = new Easy_Shortcodes();
	$plugin->run();

}
run_easy_shortcodes();


function shortcodes_post_type() {
    register_post_type( 'shortcodes',
        array(
            'labels' => array(
                'name' => __( 'Easy Shortcodes' ),
                'singular_name' => __( 'Shortcode' )
            ),
            'public' => true,
            'show_in_rest' => true,
        'supports' => array('title', 'editor'),
        'has_archive' => true,
        'rewrite'   => array( 'slug' => 'easy-shortcodes' ),
            'menu_position' => 100,
        'menu_icon' => 'dashicons-shortcode',
        'taxonomies' => array('categories') // this is IMPORTANT
        )
    );
}
add_action( 'init', 'shortcodes_post_type' );


function create_shortcodes_taxonomy() {
    register_taxonomy('categories','shortcodes',array(
        'hierarchical' => false,
        'labels' => array(
            'name' => _x( 'Categories', 'taxonomy general name' ),
            'singular_name' => _x( 'Category', 'taxonomy singular name' ),
            'menu_name' => __( 'Categories' ),
            'all_items' => __( 'All Categories' ),
            'edit_item' => __( 'Edit Category' ), 
            'update_item' => __( 'Update Category' ),
            'add_new_item' => __( 'Add Category' ),
            'new_item_name' => __( 'New Category' ),
        ),
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    ));

}
add_action( 'init', 'create_shortcodes_taxonomy', 0 );

add_filter('use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2);
function prefix_disable_gutenberg($current_status, $post_type)
{
    // Use your post type key instead of 'product'
    if ($post_type === 'shortcodes') return false;
    return $current_status;
}


add_action('restrict_manage_posts', 'tsm_filter_post_type_by_taxonomy');
function tsm_filter_post_type_by_taxonomy() {
	global $typenow;
	$post_type = 'shortcodes'; // change to your post type
	$taxonomy  = 'categories'; // change to your taxonomy
	if ($typenow == $post_type) {
		$selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => sprintf( __( 'Show all %s', 'textdomain' ), $info_taxonomy->label ),
			'taxonomy'        => $taxonomy,
			'name'            => $taxonomy,
			'orderby'         => 'name',
			'selected'        => $selected,
			'show_count'      => true,
			'hide_empty'      => true,
		));
	};
}

add_filter('parse_query', 'tsm_convert_id_to_term_in_query');
function tsm_convert_id_to_term_in_query($query) {
	global $pagenow;
	$post_type = 'shortcodes'; // change to your post type
	$taxonomy  = 'categories'; // change to your taxonomy
	$q_vars    = &$query->query_vars;
	if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
		$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
		$q_vars[$taxonomy] = $term->slug;
	}
}

add_filter('get_sample_permalink_html', 'perm', '',4);

function perm($return, $id, $new_title, $new_slug){
	global $post;
	$ret = get_post_meta( $post->ID, 'shortcode', true );
	return '<h4>Use this shortcode: ' . $ret . '</h4>';
}



add_filter( 'manage_shortcodes_posts_columns',  'shortcodes_filter_posts_columns', 3);
function shortcodes_filter_posts_columns( $columns ) {
  $columns['shortcode'] = __( 'Shortcode' );
  return $columns;
}


add_action( 'manage_shortcodes_posts_custom_column', 'smashing_realestate_column', 10, 2);
function smashing_realestate_column( $column, $post_id ) {
  // Image column
  if ( 'shortcode' === $column ) {
    echo '<p style="border: 1px solid #c3c4c7; padding: 5px 10px; width: auto; border-radius: 5px">' . get_post_meta( $post_id, 'shortcode', true ) . '</p>';
  }
}
  




add_action( 'save_post_shortcodes', 'add_shortcode_on_save', 100, 3 );

function add_shortcode_on_save( $post_id, $post, $update ) {

	$update = true;

	add_post_meta( $post_id, 'shortcode', '[' . $post->post_title .']' );
    update_post_meta( $post_id, 'shortcode', '[' . $post->post_title .']' );

}

function wp_shortcode( $atts ) {
    extract( shortcode_atts( array(
        'title' => 'something',
    ), $atts ) );


    return $title;
}
add_shortcode( 'es', 'wp_shortcode' );



echo do_shortcode( '[es title="kita"]' );





























