<?php

$tf_i2_azon_post_types = array('i2_azon_image', 'i2_azon_product_box');

function i2_azon_allowed_block_types($allowed_block_types, $post)
{
	 // if don't allow to add any thing in the post
	// global $tf_i2_azon_post_types;
	// return in_array($post->post_type, $tf_i2_azon_post_types) ? array() : $allowed_block_types;
 // allow single block type in spacific post
		if($post->post_type == 'i2_azon_image')
		{
			return array('themesfirst/i2-azon-image');
		}
		else if( $post->post_type == 'i2_azon_product_box'){ 
			return	array('themesfirst/i2-azon-product-box'); 
		}
		else{  
				return $allowed_block_types; 
		}

}
add_filter('allowed_block_types', 'i2_azon_allowed_block_types', 10, 2);


function tf_i2_azon_post_types_init()
{

	register_post_type(
		'i2_azon_image',
		array(
			'labels' => array(
				'name' => __('Images'),
				'singular_name' => __('Image')
			),

			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'capability_type'       => 'page',
			'show_in_rest' => true,
			'supports' => array('title', 'editor'),
			'show_in_menu' => 'i2_azon_setting'
		)
	);

	register_post_type(
		'i2_azon_product_box',
		array(
			'labels' => array(
				'name' => __('Product Box'),
				'singular_name' => __('Product Boxes')
			),

			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'capability_type'       => 'page',
			'show_in_rest' => true,
			'supports' => array('title', 'editor'),
			'show_in_menu' => 'i2_azon_setting'
		)
	);
}
add_action('init', 'tf_i2_azon_post_types_init');


add_filter('manage_i2_azon_image_posts_columns', 'tf_i2_azon_shortcode_columns');
add_filter('manage_i2_azon_product_box_posts_columns', 'tf_i2_azon_shortcode_columns');
function tf_i2_azon_shortcode_columns($columns)
{
	$columns = array(
		'cb' => $columns['cb'],
		'title' => $columns['title'],
		'date' => $columns['date'],
		'shortcode' => __('Shortcode', 'themesfirst'),
	);


	return $columns;
}


add_action('manage_i2_azon_image_posts_custom_column', 'tf_i2_azon_shortcode_column', 10, 2);
add_action('manage_i2_azon_product_box_posts_custom_column', 'tf_i2_azon_shortcode_column', 10, 2);
function tf_i2_azon_shortcode_column($column, $post_id)
{
	// custom post type column  
	if ('shortcode' === $column) {
		$type = 'image';
		if (isset($_GET['post_type'])) {
			switch ($_GET['post_type']) {
				case 'i2_azon_image':
					$type = 'image';
					break;
				case 'i2_azon_product_box':
					$type = 'p_box';
					break;
				default:
					$type = 'image';
					break;
			}
		}
		echo '<input type="text" onclick="this.select();" size="35" style="text-align:center" value="[i2_azon type=&quot;' . $type . '&quot; id=&quot;' . $post_id . '&quot;]" readonly="readonly">';
	}
}

add_filter('page_row_actions', 'tf_i2_azon_add_edit_links', 100, 2);
add_filter('post_row_actions', 'tf_i2_azon_add_edit_links', 100, 2);

function tf_i2_azon_add_edit_links($actions, $post)
{
	global $tf_i2_azon_post_types;
	if (in_array($post->post_type, $tf_i2_azon_post_types)) {
		$edit_url = get_edit_post_link($post->ID, 'raw');
		$edit_url = add_query_arg('post_type', $post->post_type, $edit_url);
		$actions['edit'] =  sprintf('<a href="%s" aria-label="%s">%s</a>', esc_url($edit_url), __('Edit', 'themesfirst'), __('Edit', 'themesfirst'));
		// echo '<pre>'; 
		//  print_r($actions); 
		//  echo '</pre>';
	}
	return $actions;
}

//shortcode
function i2_azon_shortcode($atts, $content = null)
{
	$atts = shortcode_atts([
		'type' => '',
		'id' => '',
	], $atts, 'i2_azon');

	if (intval($atts['id']) > 0) {
		$content = get_post_field('post_content', $atts['id']);
		$block_content = "";
		$blocks = parse_blocks($content);

		foreach ($blocks as $block) {
				$block_content .= 	render_block($block); 
		}
	 return $block_content;
	}
	return null;
}

add_shortcode('i2_azon', 'i2_azon_shortcode');


// disable classic editor for i2_azon post types
add_filter('classic_editor_plugin_settings', 'tf_i2_azon_classic_editor_plugin_settings', 10, 1);

function tf_i2_azon_classic_editor_plugin_settings($settings)
{
	if (isset($_GET['post_type'])) {
		global $tf_i2_azon_post_types;
		if (in_array($_GET['post_type'], $tf_i2_azon_post_types)) {
			return array(
				'editor' => 'block',
				'allow-users' => true
			);
		}
	}
	return $settings;
}

add_filter('classic_editor_network_default_settings', 'tf_i2_azon_classic_editor_network_default_settings', 10, 1);

function tf_i2_azon_classic_editor_network_default_settings($settings)
{
	if (isset($_GET['post_type'])) {
		global $tf_i2_azon_post_types;
		if (in_array($_GET['post_type'], $tf_i2_azon_post_types)) $settings['allow-users'] = 1;
	}
	return $settings;
}


add_filter('classic_editor_enabled_editors_for_post_type', 'tf_i2_azon_classic_editor_enabled_editors_for_post_type', 10, 2);
function tf_i2_azon_classic_editor_enabled_editors_for_post_type($editors, $post_type)
{
	global $tf_i2_azon_post_types;
	if (in_array($post_type, $tf_i2_azon_post_types)) {
		$editors['classic_editor'] = 0;
		$editors['block_editor']  = 1;
	}
	return $editors;
}
add_filter('classic_editor_enabled_editors_for_post', 'tf_i2_azon_classic_editor_enabled_editors_for_post', 10, 2);
function tf_i2_azon_classic_editor_enabled_editors_for_post($editors, $post)
{
	global $tf_i2_azon_post_types;
	if (in_array($post->post_type, $tf_i2_azon_post_types)) {
		$editors['classic_editor'] = 0;
		$editors['block_editor']  = 1;
	}
	return $editors;
}


add_filter('default_content', 'tf_i2_azon_cpt_editor_default_content', 10, 2);

function tf_i2_azon_cpt_editor_default_content($content, $post)
{

	switch ($post->post_type) {
		case 'i2_azon_image':
			$content = '<!-- wp:themesfirst/i2-azon-image  /-->';
			break;
		case 'i2_azon_product_box':
			$content = '<!-- wp:themesfirst/i2-azon-product-box  /-->';
			break;
	}
	return $content;
}
