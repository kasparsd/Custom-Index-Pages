<?php
/*
 Plugin Name: Index Pages
 Plugin URI: https://github.com/kasparsd/Custom-Index-Pages
 Description: Add any pages as a taxonomy or post index page.
 Version: 0.6
 Author: Kaspars Dambis
 Author URI: http://konstruktors.com
 Text Domain: cip
 */


add_action('admin_menu', 'cpt_atts_submenu_page');

function cpt_atts_submenu_page() {
	add_submenu_page('options-general.php', 'Index Pages', 'Index Pages', 'add_users', 'custom_index_pages', 'cip_settings');
	register_setting('cip_settings', 'cip_settings', 'cip_settings_validate');
}


// Enable support for this plugin as a symlink
add_filter('plugins_url', 'cip_plugins_url_symlink_fix', 10, 3);

function cip_plugins_url_symlink_fix($url, $path, $plugin) {
	if (strstr($plugin, basename(__FILE__)))
		return str_replace(dirname(__FILE__), '/' . basename(dirname($plugin)), $url);

	return $url;
}


add_action('admin_init', 'cip_git_updater');

function cip_git_updater() {
	// A modified version of https://github.com/jkudish/WordPress-GitHub-Plugin-Updater
	require_once('updater/updater.php');

	// Force update check
	// define('WP_GITHUB_FORCE_UPDATE', true);

	$config = array(
		'slug' => basename(dirname(__FILE__)) . '/' . basename(__FILE__),
		'proper_folder_name' => basename(dirname(__FILE__)),
		'api_url' => 'https://api.github.com/repos/kasparsd/Custom-Index-Pages',
		'raw_url' => 'https://raw.github.com/kasparsd/Custom-Index-Pages/master',
		'github_url' => 'https://github.com/kasparsd/Custom-Index-Pages',
		'zip_url' => 'http://github.com/kasparsd/Custom-Index-Pages/zipball/master',
		'sslverify' => false,
		'requires' => '3.0',
		'tested' => '3.4',
	);

	new WPGitHubUpdater($config);
}


// Disable sslverify for HTTPS plugin updates from github
add_action('http_request_args', 'cip_disable_sslverify', 10, 2);

function cip_disable_sslverify($args, $url) {
	if (strstr($url, 'simple-attributes'))
		$args['sslverify'] = false;
	
	return $args;
}


/**
 * Print the admin settings page under Settings > Index Pages
 */
function cip_settings() {
	global $wp_rewrite;

	$current_language = apply_filters('cip_current_language', 'default');
	$settings = cip_get_settings();
	?>

	<div class="wrap">
		<?php screen_icon(); ?> 
		<h2><?php _e('Index Pages'); ?></h2>

		<form id="cip_settings" method="post" action="options.php">
			<?php
				settings_fields('cip_settings');

				// Get all post types and taxonomies
				$post_types = get_post_types(array('public' => true, 'publicly_queryable' => true), 'objects');
				$taxonomies = get_taxonomies(array('public' => true), 'objects');
				$pages = get_pages(array());
			?>

			<?php if ( ! $wp_rewrite->using_permalinks() ) : ?>
				<p class="notice"><?php printf( __('Please enable <a href="%s">pretty permalinks</a> to make the best use this plugin.'), 'options-permalink.php' ); ?></p>
			<?php endif; ?>

			<h3><?php _e('Post types'); ?></h3>

			<table class="form-table">
			<?php foreach ($post_types as $post_type) : ?>
				<tr>
					<th><?php echo $post_type->labels->name; ?></th>
					<td>
						<?php if ( $post_type->has_archive ) : ?>
							<p>
								<label>
									<?php _e('Index page:'); ?>
									<?php 
										$selected = '';
										if (isset($settings[$post_type->name]['index']))
											$selected = $settings[$post_type->name]['index'];

										wp_dropdown_pages( 
											array( 
												'name' => 'cip_settings['. esc_attr($post_type->name) .'][index]', 
												'show_option_none' => __( '&mdash; Default &mdash;' ), 
												'option_none_value' => '', 
												'selected' => $selected
											)
										); 
									?>
								</label>
							</p>
						<?php endif; ?>	

						<p>
							<label>
								<?php _e('Single page:'); ?>
								<?php 
									$selected = '';
									if (isset($settings[$post_type->name]['page']))
										$selected = $settings[$post_type->name]['page'];

									wp_dropdown_pages( 
										array( 
											'name' => 'cip_settings['. esc_attr($post_type->name) .'][page]', 
											'show_option_none' => __( '&mdash; Default &mdash;' ), 
											'option_none_value' => '', 
											'selected' => $selected
										)
									); 
								?>
							</label>
						</p>
																	
						<!--
						<p>
							<label class="cip_struct">
								<?php _e('Permalink:'); ?>
								<?php 
									$prefix = '';
									$structure = '';

									if (empty($settings[$post_type->name]['struct'])) {
										if ($post_type->name == 'page' || $post_type->name == 'post' || $post_type->name == 'attachment') {
											$structure = ltrim($wp_rewrite->permalink_structure, '/');
										} else {
											$structure = '%' . $post_type->name . '%/';
											$prefix = str_replace('%' . $post_type->name . '%', '', $wp_rewrite->extra_permastructs[$post_type->name]['struct']);
										}
									} else {
										$structure = ltrim($settings[$post_type->name]['struct'], '/');
										$prefix = '/';
									}

									if (isset($settings[$post_type->name]['page']) && !empty($settings[$post_type->name]['page'])) {
										$prefix = get_permalink($settings[$post_type->name]['page']);
										$prefix = str_replace(get_bloginfo('url'), '', $prefix);
									}
								?>

								<code class="cip_prefix cip_prefix_<?php esc_attr_e($post_type->name); ?>"><?php echo $prefix; ?></code>
								<input type="text" name="cip_settings[<?php esc_attr_e($post_type->name); ?>][struct]" value="<?php esc_attr_e($structure); ?>" />
							</label>
						</p>
						-->
					</td>
				</tr>
			<?php endforeach; ?>
			</table>

			<h3><?php _e('Taxonomies'); ?></h3>
			
			<table class="form-table">
			<?php foreach ($taxonomies as $taxonomy) : ?>
				<tr>
					<th><?php echo $taxonomy->labels->name; ?></th>
					<td>
						<p>
							<label>
								<?php _e('Index page:'); ?>						
								<?php 
									$selected = '';
									if (isset($settings[$taxonomy->name]['index']))
										$selected = $settings[$taxonomy->name]['index'];

									wp_dropdown_pages( 
										array( 
											'name' => 'cip_settings['. esc_attr($taxonomy->name) .'][index]', 
											'show_option_none' => __( '&mdash; Default &mdash;' ), 
											'option_none_value' => '', 
											'selected' => $selected
										)
									); 
								?>
							</label>
						</p>
					</td>
				</tr>
			<?php endforeach; ?>	
			</table>		
		
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php esc_attr_e('Save'); ?>" />
			</p>

			<style type="text/css">
				#cip_settings .cip_struct { }
				#cip_settings .form-table { width:auto; min-width:50%; border-top:5px solid #ccc; margin-bottom:3em; }
				#cip_settings .form-table td p { margin:0.2em 0; }
				#cip_settings .form-table td { text-align:right; }
				#cip_settings .form-table th { font-weight:bold; width:auto; max-width:100px; padding:1.2em 0 0 0; }
				#cip_settings .form-table th, #cip_settings .form-table td { border-bottom:1px solid #ccc; }
				#cip_settings .notice { background:#eee; padding:0.5em 1em; border:1px solid #ccc; }
			</style>
		</form>
	</div>

	<?php	
}


function cip_settings_validate($input) {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
	
	$current_language = apply_filters( 'cip_current_language', 'default' );

	$settings = get_option('cip_settings');

	if ( !is_array( $settings ) )
		$settings = array();

	$settings[$current_language] = $input;

	return $settings;
}


/* 
	Permalink structure replacement
*/

// Modify the global $wp_rewrite to include the new rewrites
add_filter( 'init', 'add_custom_index_rewrite_rules', 11 );

/**
 * Permalink structures are generated on the fly, because that is the way
 * custom post types and taxonomies are registered/added as are the permalinks
 * to those posts, taxonomies and their archives. So we'll cache the 
 * permalinks that we're to put in front of those structures.
 */
function add_custom_index_rewrite_rules() {
	global $wp_rewrite, $wp_post_types;

	if ( empty( $wp_rewrite->extra_permastructs ) )
		return;

	$settings = cip_get_settings( 'all' );
	$current_lang = apply_filters( 'cip_current_language', 'default' );

	if ( empty( $settings ) )
		return $rules;

	// Loop through all permastructures
	foreach ($wp_rewrite->extra_permastructs as $object_type => $structure) {

		// Loop through all languages
		foreach ($settings as $lang => $cips) {
			if ( empty( $cips[ $object_type ] ) )
				continue;

			$object_settings = $cips[ $object_type ];

			if ( array_key_exists( $object_type, $wp_post_types ) && isset( $object_settings['page'] ) && ! empty( $object_settings['page'] ) ) {
				$page_struct = '/' . cip_get_page_struct_prefix( $object_settings['page'], $lang ) . '/%' . $object_type . '%';

				// Add permastructure for the current language
				if ( $current_lang == $lang )
					$wp_rewrite->extra_permastructs[ $object_type ]['struct'] = $page_struct;
				
				// Add the permastructure for $lang language
				$wp_rewrite->extra_permastructs[ $object_type . '_' . $lang ] = $wp_rewrite->extra_permastructs[ $object_type ];
				$wp_rewrite->extra_permastructs[ $object_type . '_' . $lang ]['struct'] = $page_struct;

				// Add the archive index pages on extra_rules_top
				foreach ( $wp_rewrite->extra_rules_top as $rule => $rewrite ) {
					if ( strstr( $rule, $object_type ) ) {
						$new_rule = str_replace( $object_type, cip_get_page_struct_prefix( $object_settings['index'], $lang ), $rule );
						$wp_rewrite->extra_rules_top = array( $new_rule => $rewrite ) + $wp_rewrite->extra_rules_top;
					}
				}

			} else if ( taxonomy_exists($object_type) && isset( $object_settings['index'] ) && ! empty( $object_settings['index'] ) ) {
				$page_struct = '/' . cip_get_page_struct_prefix( $object_settings['index'], $lang ) . '/%' . $object_type . '%';

				// Add permastructure for the current language
				if ( $current_lang == $lang )
					$wp_rewrite->extra_permastructs[ $object_type ]['struct'] = $page_struct;
				
				// Add the permastructure for $lang language
				$wp_rewrite->extra_permastructs[ $object_type . '_' . $lang ] = $wp_rewrite->extra_permastructs[ $object_type ];
				$wp_rewrite->extra_permastructs[ $object_type . '_' . $lang ]['struct'] = $page_struct;
			}
		}

	}

}


add_filter( 'pre_get_posts', 'cip_modify_query' );

function cip_modify_query($query) {
	global $wp_post_types;

	if ( is_admin() )
		return $query;

	if ( isset( $query->query_vars['suppress_filters'] ) && ! empty( $query->query_vars['suppress_filters'] ) )
		return $query;

	/**
	 * There is a bug in WordPress, which generates a Notice PHP warning
	 * on post_type_archive pages with selected taxonomy term.
	 */
	//if ( is_post_type_archive() && is_tax() )
	//	$query->is_post_type_archive = null;

	return $query;
}


add_filter( 'nav_menu_css_class', 'cip_correct_menu_active_parents', 10, 2 );

function cip_correct_menu_active_parents( $classes, $item ) {
	// TODO: remove anon function!!!
	if ( $item->object_id == get_option('page_for_posts') )
		$classes = array_filter( $classes, function( $i ) { 
											if ( strstr( $i, 'current_page' ) ) 
												return false; 
											else 
												return true; 
											} );

	if ( $item->object_id == cip_get_single_page_id( get_post_type() ) || $item->object_id == cip_get_index_page_id( get_post_type() ) )
		$classes[] = 'current_page_parent';

	return $classes;
}


/* 
	Settings getters
*/

function cip_get_page_struct_prefix( $page_id, $lang = 'default' ) {
	$cip_permalinks = get_option( 'cip_permalinks' );

	if ( ! is_array( $cip_permalinks ) )
		$cip_permalinks = array();

	if ( ! isset( $cip_permalinks[ $page_id ] ) ) {
		if ( $permalink = get_permalink( $page_id ) ) {
			$cip_permalinks[ $page_id ] = $permalink;
			update_option( 'cip_permalinks', $cip_permalinks );
		}
	} else {
		$permalink = $cip_permalinks[ $page_id ];
	}

	// Remove the base URL from thepermalink
	$relative_permalink = trim( str_replace( get_bloginfo('url'), '', $permalink ), '/' );
	// TODO: make this generic. Currently the language prefix has to be at example.com/lang/post-name
	$relative_permalink = ltrim( $relative_permalink, $lang . '/' );
	
	return $relative_permalink;
}

function cip_get_single_page_id( $object_name, $language = 'default' ) {
	$settings = cip_get_settings( $language );

	if ( isset( $settings[ $object_name ][ 'page' ] ) )
		return $settings[ $object_name ][ 'page' ];

	return false;
}

function cip_get_index_page_id( $object_name, $language = 'default' ) {
	$settings = cip_get_settings( $language );

	if ( isset( $settings[ $object_name ][ 'index' ] ) )
		return $settings[ $object_name ][ 'index' ];

	return false;
}

function cip_get_page_object( $page_id, $language = 'default' ) {
	$settings = cip_get_settings( $language );

	foreach ( $settings as $object_name => $pages )
		if ( $page_type =  array_search( $page_id, $pages ) )
			return array( 
					'object_name' => $object_name,
					'type' => $page_type
				);

	return false; 
}

function cip_get_settings( $language = 'default' ) {
	$settings = get_option('cip_settings');

	if ( $language == 'all' )
		return $settings;
	
	$current_language = apply_filters( 'cip_current_language', $language );

	if ( isset( $settings[ $current_language ] ) )
		return $settings[ $current_language ];

	return array();
}


// Add a public filter that can be used within themes to get the index or parent pages
add_filter( 'cip_get_current_object_page_id', 'cip_get_current_object_page_id' );

function cip_get_current_object_page_id() {
	if ( is_post_type_archive() )
		return cip_get_index_page_id( get_query_var('post_type'), apply_filters( 'cip_current_language', 'default' ) );

	return false;
}



/*
	For WPML plugin
*/

add_filter('cip_current_language', 'wpml_get_current_language');

function wpml_get_current_language($language) {
	if (defined('ICL_LANGUAGE_CODE'))
		return ICL_LANGUAGE_CODE;

	return $language;
}


add_filter('cip_post_type_link_language', 'wpml_get_post_type_language', 10, 3);

function wpml_get_post_type_language($language, $post_type, $post_id) {
	global $sitepress;

	if (!is_object($sitepress))
		return $language;

	return $sitepress->get_language_for_element($post_id, 'post_' . $post_type);
}


add_filter('cip_term_link_language', 'wpml_get_term_language', 10, 3);

function wpml_get_term_language($language, $term_id, $taxonomy) {
	global $sitepress;

	if (!is_object($sitepress))
		return $language;

	return $sitepress->get_language_for_element($term_id, 'tax_' . $taxonomy);
}

