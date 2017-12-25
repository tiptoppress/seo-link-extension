<?php
/*
Plugin Name: SEO-Link Extension
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: SEO optimization and gather clicks with Google Analytic for the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 0.2
Author URI: http://tiptoppress.com
*/

namespace termCategoryPostsPro\seoExtension;

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

const TEXTDOMAIN = 'seo-extension';

/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 4.8
 */
function no_link_filter($html,$widget,$instance) {

	if (isset($instance['no_links']) && $instance['no_links']) {
		// remove href, if exist	
		if (preg_match('/href="[^"]+"/',$html))
			$html = preg_replace('/href="[^"]+"/', "", $html);

		// change inline anchor to inline span element (start- and end tag)
		$html = str_replace('<a ','<span ',$html);
		$html = str_replace('</a>','</span>',$html);
	}

	return $html;
}

add_filter('cpwp_post_html',__NAMESPACE__.'\no_link_filter',10,3);


/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 4.8
 */
function search_engine_attribute_filter($html,$widget,$instance) {

	if (isset($instance['search_engine_attribute']) && $instance['search_engine_attribute'] != 'none') {
		// remove old rel, if exist	
		if (preg_match('/(.*)rel=".*"(.*)/',$html))
			$html = preg_replace('/rel=".*"/', "", $html);
			
		// add attribute
		switch ($instance['search_engine_attribute']) {
			case 'canonical':
				$html = str_replace('<a ','<a rel="canonical" ',$html);
				break;
			case 'nofollow':
				$html = str_replace('<a ','<a rel="nofollow" ',$html);
				break;
		}
	}
	return $html;
}

add_filter('cpwp_post_html',__NAMESPACE__.'\search_engine_attribute_filter',10,3);

/**
 * Panel "More Excerpt Options"
 *
 * @param this
 * @param instance
 * @param panel_id
 * @param panel_name
 * @param alt_prefix
 * @return true: override the widget panel
 *
 */
function form_seo_panel_filter($widget,$instance) {

	$instance = wp_parse_args( ( array ) $instance, array(	
		// extension options
		'search_engine_attribute'         => 'none',
		'no_links'                         => 'false',
	) );
	
	// extension options
	$search_engine_attribute         = $instance['search_engine_attribute'];
	$no_links                         = $instance['no_links'];

	?>
	<h4 data-panel="seo"><?php _e('SEO','categorypostspro')?></h4>
	<div>
		<p>
			<label for="<?php echo $widget->get_field_id("no_links"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("no_links"); ?>" name="<?php echo $widget->get_field_name("no_links"); ?>"<?php checked( (bool) $instance["no_links"], true ); ?> />
				<?php _e( 'No links','seo-link-extension' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("search_engine_attribute"); ?>">
				<?php _e( 'SEO friendly URLs:','seo-link-extension' ); ?>
				<select id="<?php echo $widget->get_field_id("search_engine_attribute"); ?>" name="<?php echo $widget->get_field_name("search_engine_attribute"); ?>">
					<option value="none" <?php selected($search_engine_attribute, 'none')?>><?php _e( 'None', 'category-posts' ); ?></option>
					<option value="canonical" <?php selected($search_engine_attribute, 'canonical')?>><?php _e( 'canonical', 'category-posts' ); ?></option>
					<option value="nofollow" <?php selected($search_engine_attribute, 'nofollow')?>><?php _e( 'nofollow', 'category-posts' ); ?></option>
				</select>
			</label>
		</p>
	</div>
	<?php
}

add_filter('cpwp_after_general_panel',__NAMESPACE__.'\form_seo_panel_filter',10,5);

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings($setting) {

	return wp_parse_args( ( array ) $setting, array(
		'search_engine_attribute'         => 'none',
		'no_links'                         => false,
	) );
}

add_filter('cpwp_default_settings',__NAMESPACE__.'\cpwp_default_settings');

// Plugin action links section

/**
 *  Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
 *  
 *  @return array of the widget links
 *  
 *  @since 0.1
 */
function add_action_links ( $links ) {
    $pro_link = array(
        '<a target="_blank" href="http://tiptoppress.com/term-and-category-based-posts-widget/?utm_source=widget_seoext&utm_campaign=get_pro_seoext&utm_medium=action_link">'.__('Get the pro widget needed for this extension','category-posts').'</a>',
    );
	
	$links = array_merge($pro_link, $links);
    
    return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__.'\add_action_links' );
