/**
 * Block editor support for the Term and Category based Posts block.
 *
 * The classic widget form gets its "SEO and Links Add-on" panel for free
 * through the `cpwp_after_general_panel` action in seo-link-extension.php,
 * and every field it submits lands in $instance automatically (see
 * Widget::update() in the base plugin). The block editor has no such
 * generic pass-through, so this file plugs into the two extension points
 * the block's src/edit.js and cat-posts-block.php expose instead:
 *
 * - `blocks.registerBlockType` (core WordPress filter): registers the same
 *   four settings as extra block attributes, without the base plugin's
 *   block.json needing to know about this add-on.
 * - `cpwp.afterGeneralPanel` (base plugin's own filter, in src/edit.js):
 *   renders this add-on's panel in the block inspector, in the same spot
 *   (between "General" and "Footer") the classic widget form uses.
 *
 * On the PHP side, cat-posts-block.php's `cpwp_block_instance` filter
 * (build_block_instance()) carries the values of these attributes into
 * $instance, so the existing `cpwp_post_html` filters below (title_link_filter,
 * search_engine_attribute_filter, attached_image_attributes) apply to the
 * block exactly like they do to the widget - no changes needed there.
 *
 * Plain JS on purpose (no build step): this plugin ships no webpack/JSX
 * tooling, only PHP + CSS, so this file is enqueued as-is and talks to the
 * `wp` globals WordPress core already provides in the block editor.
 *
 * @since 1.3.0
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! wp.element || ! wp.components || ! wp.i18n ) {
		return;
	}

	var addFilter     = wp.hooks.addFilter;
	var createElement  = wp.element.createElement;
	var __              = wp.i18n.__;
	var PanelBody        = wp.components.PanelBody;
	var RadioControl      = wp.components.RadioControl;
	var SelectControl     = wp.components.SelectControl;
	var ToggleControl     = wp.components.ToggleControl;

	var BLOCK_NAME = 'tiptip/term-category-posts-block';

	/**
	 * Give the block the same four settings form_seo_panel_filter() below
	 * adds to the classic widget form's $instance, with the same defaults
	 * cpwp_default_settings() uses.
	 *
	 * @param {Object} settings Block settings as they will be registered.
	 * @param {string} name     Block name.
	 *
	 * @return {Object} The (possibly extended) block settings.
	 */
	function addSeoAttributes( settings, name ) {
		if ( name !== BLOCK_NAME ) {
			return settings;
		}

		settings.attributes = Object.assign( {}, settings.attributes, {
			titleLinks: {
				type: 'string',
				default: 'default_links',
			},
			searchEngineAttribute: {
				type: 'string',
				default: 'none',
			},
			thumbnailAttributeAlt: {
				type: 'boolean',
				default: false,
			},
			thumbnailAttributeTitle: {
				type: 'boolean',
				default: false,
			},
		} );

		return settings;
	}

	addFilter( 'blocks.registerBlockType', 'seo-link-extension/add-block-attributes', addSeoAttributes );

	/**
	 * Add the "SEO and Links Add-on" panel to the block inspector, field for
	 * field the same as form_seo_panel_filter() below does for the classic
	 * widget form.
	 *
	 * @param {Array}  panels Inspector panels accumulated by earlier add-ons.
	 * @param {Object} props  { attributes, setAttributes, clientId } for this block instance.
	 *
	 * @return {Array} panels with this add-on's panel appended.
	 */
	function addSeoPanel( panels, props ) {
		var attributes    = props.attributes || {};
		var setAttributes = props.setAttributes;

		var titleLinks              = attributes.titleLinks || 'default_links';
		var searchEngineAttribute   = attributes.searchEngineAttribute || 'none';
		var thumbnailAttributeAlt   = !! attributes.thumbnailAttributeAlt;
		var thumbnailAttributeTitle = !! attributes.thumbnailAttributeTitle;

		var panel = createElement(
			PanelBody,
			{
				key: 'seo-link-extension',
				title: __( 'SEO and Links Add-on', 'seo-link-extension' ),
				initialOpen: false,
			},
			createElement( RadioControl, {
				label: __( 'Title link', 'seo-link-extension' ),
				selected: titleLinks,
				options: [
					{ label: __( 'Normal WordPress URL', 'seo-link-extension' ), value: 'default_links' },
					{ label: __( 'No links', 'seo-link-extension' ), value: 'no_links' },
					{ label: __( 'Custom links', 'seo-link-extension' ), value: 'custom_links' },
				],
				help: 'custom_links' === titleLinks ?
					__( 'Set per post/page/etc. in its "SEO Link Add-on" meta box.', 'seo-link-extension' ) :
					( 'no_links' === titleLinks ? __( 'Use <span>text</span>', 'seo-link-extension' ) : undefined ),
				onChange: function ( value ) {
					setAttributes( { titleLinks: value } );
				},
			} ),
			createElement( SelectControl, {
				label: __( 'SEO friendly URLs', 'seo-link-extension' ),
				value: searchEngineAttribute,
				options: [
					{ label: __( 'None', 'seo-link-extension' ), value: 'none' },
					{ label: __( 'canonical', 'seo-link-extension' ), value: 'canonical' },
					{ label: __( 'nofollow', 'seo-link-extension' ), value: 'nofollow' },
				],
				onChange: function ( value ) {
					setAttributes( { searchEngineAttribute: value } );
				},
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} ),
			createElement( ToggleControl, {
				label: __( 'Do not write the thumbnail ALT attribute.', 'seo-link-extension' ),
				checked: thumbnailAttributeAlt,
				onChange: function () {
					setAttributes( { thumbnailAttributeAlt: ! thumbnailAttributeAlt } );
				},
			} ),
			createElement( ToggleControl, {
				label: __( 'Do not write the thumbnail TITLE attribute (image tooltip).', 'seo-link-extension' ),
				checked: thumbnailAttributeTitle,
				onChange: function () {
					setAttributes( { thumbnailAttributeTitle: ! thumbnailAttributeTitle } );
				},
			} )
		);

		return panels.concat( panel );
	}

	addFilter( 'cpwp.afterGeneralPanel', 'seo-link-extension/inspector-panel', addSeoPanel );
} )( window.wp );
