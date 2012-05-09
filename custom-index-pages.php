<?php
/*
 Plugin Name: Index Pages
 Plugin URI: https://github.com/kasparsd/Custom-Index-Pages
 Description: Add any pages as a taxonomy or post index page.
 Version: 0.2
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

	$settings = get_option('cip_settings');

	if (isset($settings[$current_language]) && !empty($settings[$current_language]))
		$settings = $settings[$current_language];
	else
		$settings = array();

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

			<h3><?php _e('Post types'); ?></h3>

			<table class="form-table">
			<?php foreach ($post_types as $post_type) : ?>
				<tr>
					<th><?php echo $post_type->labels->name; ?></th>
					<td>
						<p>
							<label>
								<?php _e('Parent page:'); ?>
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
					<?php 
						$selected = '';
						if (isset($settings[$taxonomy->name]['page']))
							$selected = $settings[$taxonomy->name]['page'];

						wp_dropdown_pages( 
							array( 
								'name' => 'cip_settings['. esc_attr($taxonomy->name) .'][page]', 
								'show_option_none' => __( '&mdash; Default &mdash;' ), 
								'option_none_value' => '', 
								'selected' => $selected
							)
						); 
					?>
					<td>
				</tr>
			<?php endforeach; ?>	
			</table>		
		
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php esc_attr_e('Save'); ?>" />
			</p>

			<style type="text/css">
				#cip_settings .cip_struct { }	
				#cip_settings .form-table th { }
			</style>
		</form>
	</div>

	<?php	
}


function cip_settings_validate($input) {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
	
	$current_language = apply_filters('cip_current_language', 'default');

	$settings = get_option('cip_settings');
	$settings[$current_language] = $input;

	return $settings;
}


add_filter('init', 'add_custom_post_taxonomy_slugs', 500);

function add_custom_post_taxonomy_slugs() {
	global $wp_rewrite;

	if (is_admin())
		return;

	$settings = get_option('cip_settings');

	if (empty($settings))
		return;

	$current_lang = apply_filters('cip_current_language', 'default');
	$lang_permastructures = array();

	foreach ($wp_rewrite->extra_permastructs as $name => $structure) {
		foreach ($settings as $lang => $cips) {
			if (isset($cips[$name]['page']) && !empty($cips[$name]['page'])) {
				// Remove the site URL from the permalink
				$prefix = trim(str_replace(get_bloginfo('url') . '/', '', get_permalink($cips[$name]['page'])), '/');
				$structure = str_replace($name . '/', $prefix . '/', $wp_rewrite->extra_permastructs[$name]['struct']);

				if ($lang == $current_lang) {
					$lang_permastructures[$name]['struct'] = $structure;
					$lang_permastructures[$name]['with_front'] = false;
				}
				
				$lang_permastructures[$name . '_' . $lang]['struct'] = $structure;
				$lang_permastructures[$name . '_' . $lang]['with_front'] = false;
			}
		}
	}

	$wp_rewrite->extra_permastructs = array_merge($wp_rewrite->extra_permastructs, $lang_permastructures);
}


add_filter('post_type_link', 'get_post_type_link_for_lang', 20, 2);

function get_post_type_link_for_lang($link, $post) {
	global $wp_rewrite;
	
	$link_lang = apply_filters('cip_post_type_link_language', 'default', $post->post_type, $post->ID);
	$current_language = apply_filters('cip_current_language', 'default');

	if (empty($link_lang) || $current_language == $link_lang)
		return $link;

	// Get the permalink structure for the target language
	$post_link = $wp_rewrite->get_extra_permastruct($post->post_type . '_' . $link_lang);
	$post_link = str_replace("%$post->post_type%", $post->post_name, $post_link);
	$post_link = home_url( user_trailingslashit($post_link) );

	return $post_link;
}


add_filter('term_link', 'get_term_link_for_lang', 20, 3);

function get_term_link_for_lang($link, $term, $taxonomy) {
	global $wp_rewrite;
	
	$link_lang = apply_filters('cip_term_link_language', 'default', $term->term_id, $taxonomy);
	$current_language = apply_filters('cip_current_language', 'default');

	if (empty($link_lang) || $current_language == $link_lang)
		return $link;

	// Get the permalink structure for the target language
	$term_link = $wp_rewrite->get_extra_permastruct($taxonomy . '_' . $link_lang);
	$term_link = str_replace("%$taxonomy%", $term->slug, $term_link);
	$term_link = home_url( user_trailingslashit($term_link) );

	return $term_link;
}


add_filter('rewrite_rules_array', 'add_custom_index_page_rewrites');

function add_custom_index_page_rewrites($rules) {
	$rules_mod = array();

	$settings = get_option('cip_settings');

	if (empty($settings))
		return $rules;

	foreach ($rules as $pattern => $replace) {
			$replace_with = array();
			
			foreach ($settings as $lang => $cips) {
				foreach ($cips as $cip_name => $cip_settings) {
					if (strpos($pattern, $cip_name) !== false && !empty($cip_settings['page'])) {
						$replace_with[$lang] = array(
								'name' => $cip_name,
								'post_id' => $cip_settings['page'],
							);
						if (isset($cip_settings['struct']) && !empty($cip_settings['struct']))
							$replace_with[$lang]['struct'] = trim($cip_settings['struct'], '/');
						break;
					}
				}
			}

			if (!empty($replace_with)) {
				foreach ($replace_with as $lang => $to_replace) {
					$relative_permalink = trim(str_replace(get_bloginfo('url') . '/', '', get_permalink($to_replace['post_id'])), '/');
					$relative_permalink = ltrim($relative_permalink, $lang . '/');
					$rules_mod[str_replace($to_replace['name'], $relative_permalink, $pattern)] = $replace;
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

