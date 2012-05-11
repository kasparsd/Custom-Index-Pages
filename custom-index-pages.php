<?php
/*
 Plugin Name: Index Pages
 Plugin URI: https://github.com/kasparsd/Custom-Index-Pages
 Description: Add any pages as a taxonomy or post index page.
 Version: 0.4
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
	$settings[$current_language] = $input;

	//$settings = array();
	return $settings;
}


add_filter( 'init', 'add_custom_post_taxonomy_slugs', 100 );

function add_custom_post_taxonomy_slugs() {
	global $wp_rewrite;

	if ( is_admin() || ! $wp_rewrite->using_permalinks() )
		return;

	$settings = cip_get_settings( 'all' );

	if ( empty($settings) )
		return;

	// We don't need this, because all of our rewrites are in the extra_permastructs already
	$wp_rewrite->extra_rules_top = array();

	$current_lang = apply_filters( 'cip_current_language', 'default' );
	$lang_permastructures = array();

	foreach ( $wp_rewrite->extra_permastructs as $name => $structure ) {
		foreach ( $settings as $lang => $cips ) {
			$page_id = 0;

			// Change only taxonomy and post_type NON index rewrites
			if ( post_type_exists($name) && isset( $cips[$name]['page'] ) && ! empty( $cips[$name]['page'] ) )
				$page_id = $cips[$name]['page'];
			else if ( isset( $cips[$name]['index'] ) && ! empty( $cips[$name]['index'] ) )
				$page_id = $cips[$name]['index'];
			else
				continue;
			
			// Remove the site URL from the permalink
			$prefix = trim( str_replace( get_bloginfo('url') . '/', '', get_permalink($page_id) ), '/' );
			$structure = str_replace($name . '/', $prefix . '/', $wp_rewrite->extra_permastructs[$name]['struct']);
			
			if ( $lang == $current_lang ) {
				// Change the permastructures for the current language by default
				$lang_permastructures[$name] = $wp_rewrite->extra_permastructs[$name];
				$lang_permastructures[$name]['struct'] = $structure;
				$lang_permastructures[$name]['with_front'] = false;
			}
			
			// Add permastructures for other languages, in case we need to find the permalink of the translation
			$lang_permastructures[$name . '_' . $lang] = $wp_rewrite->extra_permastructs[$name];
			$lang_permastructures[$name . '_' . $lang]['struct'] = $structure;
			$lang_permastructures[$name . '_' . $lang]['with_front'] = false;
		}
	}

	$wp_rewrite->extra_permastructs = array_merge($wp_rewrite->extra_permastructs, $lang_permastructures);
	//print_r($wp_rewrite->extra_permastructs);
}


add_filter('post_type_link', 'get_post_type_link_for_lang', 20, 2);

function get_post_type_link_for_lang($link, $post) {
	global $wp_rewrite;

	$link_lang = apply_filters('cip_post_type_link_language', 'default', $post->post_type, $post->ID);
	$current_language = apply_filters('cip_current_language', 'default');

	if (empty($link_lang) || $current_language == $link_lang)
		return $link;

	// Get the permalink structure for the target language
	$post_link_structure = $wp_rewrite->get_extra_permastruct($post->post_type . '_' . $link_lang);

	if ( empty( $post_link_structure ) )
		return $link;

	$post_link = str_replace("%$post->post_type%", $post->post_name, $post_link_structure);
	$post_link = home_url( user_trailingslashit($post_link) );

	return $post_link;
}


//add_filter('term_link', 'get_term_link_for_lang', 20, 3);

function get_term_link_for_lang($link, $term, $taxonomy) {
	global $wp_rewrite;
	
	$link_lang = apply_filters( 'cip_term_link_language', 'default', $term->term_id, $taxonomy );
	$current_language = apply_filters( 'cip_current_language', 'default' );
	
	print_r($current_language);

	if ( empty($link_lang) || $current_language == $link_lang )
		return $link;
	
	// Get the permalink structure for the target language
	$term_link_structure = $wp_rewrite->get_extra_permastruct( $taxonomy . '_' . $link_lang );

	if ( empty( $term_link_structure ) )
		return $link;

	$term_link = str_replace( "%$taxonomy%", $term->slug, $term_link_structure );
	$term_link = home_url( user_trailingslashit($term_link) );

	return $term_link;
}


add_filter( 'rewrite_rules_array', 'add_custom_index_page_rewrites', 200);

function add_custom_index_page_rewrites($rules) {
	$rules_mod = array();

	$settings = cip_get_settings( 'all' );

	if (empty($settings))
		return $rules;

	foreach ($rules as $pattern => $replace) {
			$replace_with = array();
			
			foreach ($settings as $lang => $cips) {

				foreach ($cips as $object_type => $cip_settings) {
					if ( strpos( $pattern, $object_type ) === false )
						continue;

					if ( ! empty( $cip_settings['page'] ) && ! strpos( $replace, 'post_type' ) ) {
						$replace_with[$lang] = array(
								'name' => $object_type,
								'post_id' => $cip_settings['page'],
							);

						if (isset($cip_settings['struct']) && !empty($cip_settings['struct']))
							$replace_with[$lang]['struct'] = trim($cip_settings['struct'], '/');
						
						break;
					} else if ( ! empty( $cip_settings['index'] ) ) {
						$replace_with[$lang] = array(
								'name' => $object_type,
								'post_id' => $cip_settings['index'],
							);
						
						if (isset($cip_settings['struct']) && !empty($cip_settings['struct']))
							$replace_with[$lang]['struct'] = trim($cip_settings['struct'], '/');
						
						break;
					}
				}
			}

			if ( !empty($replace_with) ) {
				foreach ( $replace_with as $lang => $to_replace ) {
					// Remove the base URL from thepermalink
					$relative_permalink = trim(str_replace(get_bloginfo('url') . '/', '', get_permalink($to_replace['post_id'])), '/');
					$relative_permalink = ltrim($relative_permalink, $lang . '/');
					$rules_mod[ str_replace($to_replace['name'], $relative_permalink, $pattern) ] = $replace;
				}
			} else {
				$rules_mod[$pattern] = $replace;
			}
	}

	//print_r($rules_mod);
	//print_r($rules);
	//die();
	//return $rules;
	return $rules_mod;
}


//add_filter( 'pre_get_posts',  'cip_modify_query' );

function cip_modify_query($query) {
	if ( is_admin() )
		return $query;

	if ( isset( $query->query_vars['suppress_filters'] ) && ! empty( $query->query_vars['suppress_filters'] ) )
		return $query;
	
	$settings = cip_get_settings();

	if ( empty( $settings ) )
		return $query;

	if ( isset( $query->queried_object_id ) )
		$page_id = $query->queried_object_id;
	else
		$page_id = $query->get('page_id');

	$page_object = cip_get_page_object( $page_id );

	if ( empty( $page_object ) )
		return $query;

	if ( $page_object['type'] != 'index' )
		return $query;

	if ( ! post_type_exists( $page_object['object_name'] ) )
		return $query;
	
	$query->set( 'post_type', $page_object['object_name'] );
	$query->set( 'page_id', 0 );
	$query->set( 'pagename', null );

	$query->is_singular = false;
	$query->is_page = false;
	$query->is_archive = true;
	$query->is_post_type_archive = true;
	$query->queried_object = get_page( $page_id );
	$query->queried_object_id = $page_id;

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

