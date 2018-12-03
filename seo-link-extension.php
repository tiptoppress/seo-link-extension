<?php
/*
Plugin Name: SEO Link Add-on
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: SEO on-page optimization and gather clicks with Google Analytic for the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 1.1.0
Author URI: http://tiptoppress.com
*/

namespace termCategoryPostsPro\seoExtension;

// Don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TEXTDOMAIN     = 'seo-link-extension';
const MINBASEVERSION = '4.7.1';


/**
 * Enqueue admin UI styles
 *
 * @since 1.1.0
 */
function admin_scripts( $hook ) {

	if ( 'widgets.php' === $hook || 'post.php' === $hook ) { // enqueue only for widget admin and customizer (add if post.php: fix make widget SiteOrigin Page Builder plugin, GH issue #181)

		wp_enqueue_style( 'seo-link-extension', plugins_url( 'css/seo-link-styles.css', __FILE__ ) );
	}
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_scripts' ); // "called on widgets.php and costumizer since 3.9

/**
 * Save meta box params
 *
 * @since 1.1.0
 */
function save_post_types_meta( $post_id ){
	global $post; 

	/* Verify the nonce before proceeding. */
	if ( ! isset( $_POST['post_class_nonce'] ) || ! wp_verify_nonce( $_POST['post_class_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	$url_options = array( 'url', 'target' );
	foreach ( $url_options as $option ) {
		/* Get the posted data and sanitize it for use. */
		$new_meta_value = ( isset( $_POST[ 'post-' . $option ] ) ? $_POST[ 'post-' . $option ] : '' );

		/* Get the meta keys. */
		$meta_key = 'post_' . $option;

		/* Get the meta value of the custom field key. */
		$meta_value = get_post_meta( $post_id, $meta_key, true );

		if ( $new_meta_value && '' === $meta_value ) {
			/* If a new meta value was added and there was no previous value, add it. */
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		} elseif ( $new_meta_value && $new_meta_value !== $meta_value ) {
			/* If the new meta value does not match the old value, update it. */
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		} elseif ( '' === $new_meta_value && $meta_value ) {
			/* If there is no new meta value but an old value exists, delete it. */
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}
	}
}

/**
 * Meta box admin UI
 *
 * @since 1.1.0
 */
function post_class_meta_box( $post ) { ?>

<?php wp_nonce_field( basename( __FILE__ ), 'post_class_nonce' ); ?>

	<h4>Custom links</h4>
	<p>
	<p>
		<label for="post-url">
			<?php
				esc_html_e( 'Custom URL:', 'seo-link-extension' );
			?>
		</label>
	</p>
		<input class="widefat" type="text" name="post-url" id="post-url" value="<?php echo esc_attr( get_post_meta( $post->ID, 'post_url', true ) ); ?>" size="30" />
	<p class="howto">Example http://mypage.com</p>
	</p>
	<p>
		<label for="post-target">
		<input class="widefat" type="checkbox" name="post-target" id="post-target" <?php checked( (bool) get_post_meta( $post->ID, 'post_target' ), 1 ); ?> />
			<?php
				esc_html_e( 'Open link in a new window', 'seo-link-extension' );
			?>
		<p class="howto">Adds target attribute _blank</p>
	</label>
	</p>
<?php
}


/**
 * Add the meta box
 *
 * @since 1.1.0
 */
function add_post_meta_boxes() {

	add_meta_box(
		'seo-link-extension', // Unique ID
		esc_html__( 'SEO Link Add-on', 'example' ), // Title
		__NAMESPACE__ . '\post_class_meta_box', // Callback function
		get_post_types(), // all post types admin
		'side', // Context
		'default' // Priority
	);
}

/**
 * Action hooks for add and save the meta box
 *
 * @since 1.1.0
 */
function post_meta_boxes_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_post_meta_boxes' );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', __NAMESPACE__ . '\save_post_types_meta' );

	/* Edit post meta for attachment/Media. */
	add_action( 'edit_attachment', __NAMESPACE__ . '\save_attachment_meta' );
}

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', __NAMESPACE__ . '\post_meta_boxes_setup' );
add_action( 'load-post-new.php', __NAMESPACE__ . '\post_meta_boxes_setup' );


/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 0.2
 */
function title_link_filter( $html, $widget, $instance ) {
	global $post;

	if ( isset( $instance['title_links'] ) && 'no_links' === $instance['title_links'] ) {
		// remove href, if exist
		if ( preg_match( '/href="[^"]+"/', $html ) ) {
			$html = preg_replace( '/href="[^"]+"/', '', $html );
		}

		// change inline anchor to inline span element (start- and end tag)
		$html = str_replace( '<a ', '<span ', $html );
		$html = str_replace( '</a>', '</span>', $html );
	} elseif ( isset( $instance['title_links'] ) && 'custom_links' === $instance['title_links'] ) {
		// set new URL link
		if ( preg_match( '/href="[^"]+"/', $html ) ) {
			// retrieve the global notice for the current post;
			$post_class = get_post_meta( $post->ID, 'post_url', true );
			$html       = preg_replace( '/href="[^"]+"/', " href='" . $post_class . "' ", $html );
		}

		$post_target = get_post_meta( $post->ID, 'post_target', true );
		if ( isset( $post_target ) && $post_target ) {
			$html = str_replace( '<a ', '<a target="_blank" ', $html );
		}
	}
	return $html;
}

add_filter( 'cpwp_post_html', __NAMESPACE__ . '\title_link_filter', 10, 3 );


/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 0.2
 */
function search_engine_attribute_filter( $html, $widget, $instance ) {

	if ( isset( $instance['search_engine_attribute'] ) && 'none' !== $instance['search_engine_attribute'] ) {
		// remove old rel, if exist
		if ( preg_match( '/(.*)rel=".*"(.*)/', $html ) ) {
			$html = preg_replace( '/rel=".*"/', '', $html );
		}

		// add attribute
		switch ( $instance['search_engine_attribute'] ) {
			case 'canonical':
				$html = str_replace( '<a ', '<a rel="canonical" ', $html );
				break;
			case 'nofollow':
				$html = str_replace( '<a ', '<a rel="nofollow" ', $html );
				break;
		}
	}
	return $html;
}

add_filter( 'cpwp_post_html', __NAMESPACE__ . '\search_engine_attribute_filter', 10, 3 );

/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 1.1.0
 */
function attached_image_attributes( $html, $widget, $instance ) {

	if ( isset( $instance['thumbnail_attribute_alt'] ) && $instance['thumbnail_attribute_alt'] ) {
		// remove old rel, if exist
		if ( preg_match( '/(.*)alt=".*"(.*)/', $html ) ) {
			$html = preg_replace( '/alt="[^"]*"/', '', $html );
		}
	}

	if ( isset( $instance['thumbnail_attribute_title'] ) && $instance['thumbnail_attribute_title'] ) {
		// remove old rel, if exist
		if ( preg_match( '/(.*)title=".*"(.*)/', $html ) ) {
			$html = preg_replace( '/title="[^"]*"/', '', $html );
		}
	}
	return $html;
}

add_filter( 'cpwp_post_html', __NAMESPACE__ . '\attached_image_attributes', 10, 3 );


/**
* Check the Term and Category based Posts Widget version
*
*  @return Base widget supporteds this Extension version
*
*/
function version_check( $min_base_version = MINBASEVERSION ) {
	$min_base_version = explode( '.', $min_base_version );

	if ( ! defined( '\termcategoryPostsPro\VERSION' ) ) {
		return false;
	}
	$installed_base_version = explode( '.', \termcategoryPostsPro\VERSION );

	$ret = ( $min_base_version[0] < $installed_base_version[0] ) ||
			( $min_base_version[0] === $installed_base_version[0] && $min_base_version[1] <= $installed_base_version[1] );

	return $ret;
}

function base_widget_check() {
	if ( ! defined( '\termcategoryPostsPro\WIDGET_BASE_ID' ) ) {
		return false;
	}
	return true;
}

/**
* Write admin notice if a higher version is needed
*
*/
function version_notice() {
	if ( ! version_check() ) {
		?>
		<div class="update-nag notice">
			<p><?php printf( esc_attr( 'The SEO-Link Extension needs the Term and Category based Posts Wiedget version %1$s or higher. It is possible that some features are not available. Please <a href="%1$s">update</a>.', 'category-posts' ), MINBASEVERSION, admin_url( 'plugins.php' ) ); ?></p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', __NAMESPACE__ . '\version_notice' );

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
function form_seo_panel_filter( $widget, $instance ) {

	if ( ! version_check( '4.7.1' ) ) {
		return;
	}

	$instance = wp_parse_args( (array) $instance, array(
		// extension options
		'search_engine_attribute'   => 'none',
		'title_links'               => 'default_links',
		'thumbnail_attribute_alt'   => '',
		'thumbnail_attribute_title' => '',
	) );

	// extension options
	$search_engine_attribute   = $instance['search_engine_attribute'];
	$title_links               = $instance['title_links'];
	$thumbnail_attribute_alt   = $instance['thumbnail_attribute_alt'];
	$thumbnail_attribute_title = $instance['thumbnail_attribute_title'];

	?>
	<h4 data-panel="seo"><?php esc_html_e( 'SEO and Links Add-on', 'categorypostspro' ); ?></h4>
	<div>
		<?php if ( version_check( '4.7.1' ) ) : ?>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( 'title_links_default_links' ) ); ?>">
				<input type="radio" value="default_links" class="checkbox" id="<?php echo esc_attr( $widget->get_field_id( 'title_links_default_links' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'title_links' ) ); ?>"
				<?php
				if ( 'default_links' === $instance['title_links'] ) {
					echo 'checked="checked"';
				};
				?>
				/>
				<?php esc_html_e( 'Normal WordPress URL', 'seo-link-extension' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( 'title_links_no_links' ) ); ?>">
				<input type="radio" value='no_links' class="checkbox" id="<?php echo esc_attr( $widget->get_field_id( 'title_links_no_links' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'title_links' ) ); ?>"
				<?php
				if ( 'no_links' === $instance['title_links'] ) {
					echo 'checked="checked"';
				};
				?>
				/>
				<?php esc_html_e( 'No links', 'seo-link-extension' ); ?>
				<p class="howto">Use &lt;span&gt;text&lt;/span&gt;</p>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( 'title_links_custom_links' ) ); ?>">
				<input type="radio" value="custom_links" class="checkbox" id="<?php echo esc_attr( $widget->get_field_id( 'title_links_custom_links' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'title_links' ) ); ?>"
				<?php
				if ( 'custom_links' === $instance['title_links'] ) {
					echo 'checked="checked"';
				};
				?>
				/>
				<?php esc_html_e( 'Custom links', 'seo-link-extension' ); ?>
				<p class="howto">Set in post, page, etc. edit admin</p>
			</label>
		</p>
		<?php endif; ?>
		<p>
			<label for="<?php echo esc_attr( $widget->get_field_id( 'search_engine_attribute' ) ); ?>">
				<?php esc_html_e( 'SEO friendly URLs:', 'seo-link-extension' ); ?>
				<select id="<?php echo esc_attr( $widget->get_field_id( 'search_engine_attribute' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'search_engine_attribute' ) ); ?>">
					<option value="none" <?php selected( $search_engine_attribute, 'none' ); ?>><?php esc_html_e( 'None', 'category-posts' ); ?></option>
					<option value="canonical" <?php selected( $search_engine_attribute, 'canonical' ); ?>><?php esc_html_e( 'canonical', 'category-posts' ); ?></option>
					<option value="nofollow" <?php selected( $search_engine_attribute, 'nofollow' ); ?>><?php esc_html_e( 'nofollow', 'category-posts' ); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("thumbnail_attribute_alt"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("thumbnail_attribute_alt"); ?>" name="<?php echo $widget->get_field_name("thumbnail_attribute_alt"); ?>"
				<?php
				if ( $thumbnail_attribute_alt == true ) {
					echo 'checked="checked"';
				};
				?> />
				<?php esc_html_e( 'Do not write the thumbnail ALT attribute.', 'seo-link-extension' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("thumbnail_attribute_title"); ?>">
				<input type="checkbox" class="checkbox" id="<?php echo $widget->get_field_id("thumbnail_attribute_title"); ?>" name="<?php echo $widget->get_field_name("thumbnail_attribute_title"); ?>"
				<?php
				if ( $thumbnail_attribute_title == true ) {
					echo 'checked="checked"';
				};
				?> />
				<?php esc_html_e( 'Do not write the thumbnail TITLE attribute (Image tooltip).', 'seo-link-extension' ); ?>
			</label>
		</p>
	</div>
	<?php
}

add_filter( 'cpwp_after_general_panel', __NAMESPACE__ . '\form_seo_panel_filter', 10, 5 );

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings( $setting ) {

	return wp_parse_args( (array) $setting, array(
		'search_engine_attribute'   => 'none',
		'title_links'               => 'default_links',
		'thumbnail_attribute_alt'   => '',
		'thumbnail_attribute_title' => '',
	) );
}

add_filter( 'cpwp_default_settings', __NAMESPACE__ . '\cpwp_default_settings' );

// Plugin action links section

/**
 *  Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
 *
 *  @return array of the widget links
 *
 *  @since 0.1
 */
function add_action_links( $links ) {

	if( ! base_widget_check() ) {
		$pro_link = array(
			'<a target="_blank" href="http://tiptoppress.com/term-and-category-based-posts-widget/">' . __( 'Get the pro widget needed for this add-on', 'category-posts' ) . '</a>',
		);
		$links    = array_merge( $pro_link, $links );
	}
	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_action_links' );
