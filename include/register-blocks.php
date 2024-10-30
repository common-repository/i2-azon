<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

function i2_azon_block_init()
{

	$options = get_option('i2_azon_options', ThemesFirst\Plugin\I2Azon\i2_azon_options_default());
	// Register block styles for both frontend + backend.

	wp_register_style(
		'i2_azon_style_css', // Handle.
		I2_AZON_BASE_URL . 'dist/css/style.css', // Block style CSS.
		array('wp-editor'), // Dependency to include the CSS after it.
		I2_AZON_VER // Version
	);
	// Register block editor script for backend.
	wp_register_script(
		'i2_azon_block_js', // Handle.
		I2_AZON_BASE_URL . 'dist/js/blocks.js', // Block.build.js: We register the block here. Built with Webpack.
		array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-rich-text', 'wp-editor', 'wp-api-fetch', 'lodash'), 
		// Dependencies, defined above.
		I2_AZON_VER, // Version
		true // Enqueue the script in the footer.
	);

	// Register block editor styles for backend.
	wp_register_style(
		'i2_azon_editor_css', // Handle.
		I2_AZON_BASE_URL . 'dist/css/blockseditor.css', // Block editor CSS.
		array('wp-edit-blocks'), // Dependency to include the CSS after it.
		I2_AZON_VER // Version
	);

	wp_add_inline_style('i2_azon_style_css', '@media only screen and (max-width: ' . intval($options['mobile_breakpoint']) . 'px) { .tf-i2-product {flex-direction: column; }.tf-i2-product > div {width: 100% !important; }.tf-i2-product > div.tf-i2-image img {margin:0 auto; } }');

	wp_localize_script(
		'i2_azon_block_js',
		'tfGlobal', // Array containing dynamic data for a JS Global.
		[
			'pluginDirPath' => plugin_dir_path(__DIR__),
			'pluginDirUrl'  => plugin_dir_url(__DIR__),
			'homeUrl' => home_url(),
			'debug' => I2_AZON_DEBUG,
			'storeUrl' => "https://www.amazon.{$options['amz_store']}",
			'partnerTagForLink' => $options['partner_tag_for_link'],
			'mobile_breakpoint' => $options['mobile_breakpoint'],
			'add_proxy_link' => $options['add_proxy_link'],
			'proxy_link_slug' => $options['proxy_link_slug'],
			'donotAddLink' => $options['partner_tag_donot_add_link']
		]
	);


	register_block_type(
		'themesfirst/i2-azon-image',
		array(
			'render_callback' => 'i2_azon_load_single_image',
			// Enqueue blocks.style.build.css on both frontend & backend.
			'style'         => 'i2_azon_style_css',
			// Enqueue blocks.build.js in the editor only.
			'editor_script' => 'i2_azon_block_js',
			// Enqueue blocks.editor.build.css in the editor only.
			'editor_style'  => 'i2_azon_editor_css',
			'attributes' => [
				'item_ids' => [
					'type' => 'array',
					'default' => []
				],
				'useLink' => [
					'type' => 'boolean',
					'default' => true
				],
				'customLink' => [
					'type' => 'string',
					'default' => ''
				],
			],
		)
	);

	register_block_type(
		'themesfirst/i2-azon-product-box',
		array(
			'render_callback' => 'i2_azon_load_single_image',
			// Enqueue blocks.style.build.css on both frontend & backend.
			'style'         => 'i2_azon_style_css',
			// Enqueue blocks.build.js in the editor only.
			'editor_script' => 'i2_azon_block_js',
			// Enqueue blocks.editor.build.css in the editor only.
			'editor_style'  => 'i2_azon_editor_css',
			'attributes' => [
				'item_ids' => [
					'type' => 'array',
					'default' => []
				],
				'useLink' => [
					'type' => 'boolean',
					'default' => true
				],
				'customLink' => [
					'type' => 'string',
					'default' => ''
				],
			],
		)
	);
}

add_action('init', 'i2_azon_block_init');

function i2_azon_load_single_image($atts, $content)
{

	$atts = shortcode_atts([
		'item_ids' => [],
		'useLink' => true,
		'customLink' => ''
	], $atts);

	// echo '<pre>'; 
	//  print_r($atts); 
	//  var_dump($content); 
	//  echo '</pre>';

	if (!empty($atts['item_ids']) && $atts['useLink'] == true) {
		if (strlen($atts['customLink']) == 0) {
			return  str_replace('##AFFILIATELINK##', ThemesFirst\Plugin\I2Azon\i2azon_get_amazon_link($atts['item_ids'][0]), $content);
		} else {
			return  str_replace('##AFFILIATELINK##', $atts['customLink'], $content);
		}
	}
	return str_replace('##AFFILIATELINK##', '#', $content);
}

add_filter('block_categories', 'tf_register_block_category', 10, 2);
function tf_register_block_category($categories, $post)
{
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'themesfirst',
				'title' => __('Themes First', 'themesfirst'),
			),
		)
	);
}
