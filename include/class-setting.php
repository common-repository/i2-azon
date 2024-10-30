<?php

namespace ThemesFirst\Plugin\I2Azon;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

function i2_azon_options_default()
{
	$options = array(
		'allow_browser_extention_to_update' => 0,
		'i2_azon_browser_key' => wp_generate_uuid4(),
		'max_allowed_browser_update_in_a_day' => 50,

		'partner_tag_for_link' => '',
		'partner_tag_donot_add_link' => 1,

		'add_proxy_link' => 0,
		'proxy_link_slug' => 'i2azon',
		'add_proxy_image' => 0,

		'mobile_breakpoint' => 768,

		'amz_store' => 'com',
		'use_amazon_api_key' => 0,
		'partner_tag' => '',
		'access_key' => '',
		'secret_key' => '',
		'api_key_connected' => 0,
		'schedule' => ''
	);
	return $options;
}

if (!class_exists('i2_AZON_Settings_Page')) {

	class i2_AZON_Settings_Page
	{
		private $options;
		public function __construct()
		{
			$this->options = get_option('i2_azon_options', i2_azon_options_default());

			add_action('admin_menu', array($this, 'add_plugin_page'));
			add_action('admin_init', array($this, 'page_init'));
			add_action('admin_enqueue_scripts', array($this, 'i2_azon_add_script'));
		}

		public function i2_azon_add_script()
		{
			if (current_user_can('manage_options') && isset($_GET["page"])  && $_GET["page"] == "i2_azon_setting_page") {
				wp_enqueue_style('i2-azon-setting-style', plugins_url('dist/css/style.css', I2_AZON_BASE_FILE));
				wp_enqueue_script('i2-azon-setting-js', plugins_url('dist/js/admin.js', I2_AZON_BASE_FILE), array('jquery'), '', true);
			}
		}

		public function add_plugin_page()
		{

			add_menu_page(
				__('i2 AZON', 'themesfirst'),
				__('i2 AZON', 'themesfirst'),
				'manage_options',
				'i2_azon_setting',
				'',
				'dashicons-feedback',
				83
			);

			add_submenu_page(
				'i2_azon_setting',
				__('Setting', 'themesfirst'),
				__('Setting', 'themesfirst'),
				'manage_options',
				'i2_azon_setting_page',
				array($this, 'create_admin_page'),
				0
			);
		}

		/**
		 * Options page callback
		 */
		public function create_admin_page()
		{
?>
			<div id="i2_azon_setting_page" class="wrap">
				<h1><?php _e('i2 AZON Settings', 'themesfirst') ?></h1>
				<?php settings_errors(); ?>
				<div id="i2-azon-setting-tab">
					<div class="nav-tab-wrapper">
						<a href="#tab-amazon" class="nav-tab nav-tab-active"><i class="dashicons dashicons-admin-generic"></i> <?php _e('Setting', 'themesfirst') ?></a>
						<a href="#tab-activation" class="nav-tab"><i class="dashicons dashicons-admin-site-alt"></i> <?php _e('Browser extension', 'themesfirst') ?></a>
						<!-- <a href="#" class="nav-tab"><?php _e('Section', 'themesfirst') ?></a> -->
					</div>
					<div class="tab-content-wrapper postbox">
						<div class="inside">
							<form method="post" action="options.php">
								<?php
								// echo wp_generate_uuid4();
								//  var_dump(\uniqid ('', true));
								// echo '<pre>'; 
								// var_dump($this->options); 
								// print_r(get_option('i2_azon_options', i2_azon_options_default()));
								// echo '</pre>';
								// This prints out all hidden setting fields
								settings_fields('i2_azon_options'); ?>

								<div id="tab-activation" class="tab-content" style="display: none;">
									<?php
									do_settings_sections('i2_azon_activation');
									?>
								</div>
								<div id="tab-amazon" class="tab-content active">
									<?php
									do_settings_sections('i2_azon_amazon');
									?>
								</div>
								<?php submit_button(); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
<?php
		}

		/**
		 * Register and add settings
		 */
		public function page_init()
		{
			register_setting(
				'i2_azon_options', // Option group
				'i2_azon_options', // Option name
				array($this, 'sanitize') // Sanitize
			);

			add_settings_section(
				'i2_azon_section_activation', // ID
				'Browser Extention Settings', // Title
				array($this, 'print_section_info'), // Callback
				'i2_azon_activation' // Page
			);
			add_settings_section(
				'i2_azon_section_amazon', // ID
				'Amazon Settings', // Title
				array($this, 'print_amazon_section_info'), // Callback
				'i2_azon_amazon' // Page
			);

			add_settings_field(
				'allow_browser_extention_to_update',
				__('Allow Extention to Update', 'themesfirst'),
				array($this, 'i2_azon_callback_checkbox_field'),
				'i2_azon_activation',
				'i2_azon_section_activation',
				['id' => 'allow_browser_extention_to_update']
			);
			add_settings_field(
				'max_allowed_browser_update_in_a_day',
				__('Max update in a day', 'themesfirst'),
				array($this, 'i2_azon_callback_text_field'),
				'i2_azon_activation',
				'i2_azon_section_activation',
				['id' => 'max_allowed_browser_update_in_a_day', 'size' => 50, 'type' => 'number']
			);

			add_settings_field(
				'i2_azon_browser_key', // ID
				'Browser key', // Title 
				array($this, 'i2_azon_callback_text_field'), // Callback
				'i2_azon_activation', // Page
				'i2_azon_section_activation', // Section    
				['id' => 'i2_azon_browser_key', 'onclick' => 'this.select();', 'type' => 'password']
			);
			add_settings_field(
				'update_browser_key',
				__('Update browser key', 'themesfirst'),
				array($this, 'i2_azon_callback_checkbox_field'),
				'i2_azon_activation',
				'i2_azon_section_activation',
				['id' => 'update_browser_key']
			);


			add_settings_field(
				'amz_store',
				__('Your Amazon Store', 'i2-azon'),
				array($this, 'i2_azon_callback_select_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'amz_store']
			);

			add_settings_field(
				'partner_tag_for_link', // ID
				'Enter your referral tag for Link', // Title 
				array($this, 'i2_azon_callback_text_field'), // Callback
				'i2_azon_amazon', // Page
				'i2_azon_section_amazon', // Section           
				['id' => 'partner_tag_for_link', 'size' => 50, 'helptext' => __('This tag should be your partner/referral tag, which will appear in links (optional), it only required if you are not using your own api key to fetch data or want to be a diferent referral tag from API', 'themesfirst')]
			);
			add_settings_field(
				'partner_tag_donot_add_link',
				__('Don\'t add affiliate link', 'themesfirst'),
				array($this, 'i2_azon_callback_checkbox_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'partner_tag_donot_add_link']
			);

			add_settings_field(
				'mobile_breakpoint',
				__('Mobile Breakpoint', 'themesfirst'),
				array($this, 'i2_azon_callback_text_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'mobile_breakpoint', 'size' => 50, 'suffix' => 'px']
			);

			$helplink_for_proxyLink = home_url() . '/' . $this->options['proxy_link_slug'] . '/B07RF1XD36';
			add_settings_field(
				'add_proxy_link',
				__('Add proxy link', 'themesfirst'),
				array($this, 'i2_azon_callback_checkbox_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'add_proxy_link', 'helptext' => __('Your link will be like', 'themesfirst') . ' ' . $helplink_for_proxyLink]
			);

			add_settings_field(
				'proxy_link_slug',
				__('Proxy slug', 'themesfirst'),
				array($this, 'i2_azon_callback_text_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'proxy_link_slug', 'myclass' => $this->options['add_proxy_link'] == 1 ? 'i2azon-proxy' : 'i2azon-proxy i2azon-hidden']
			);


			add_settings_field(
				'use_amazon_api_key',
				__('Do you have amazon API keys?', 'themesfirst'),
				array($this, 'i2_azon_callback_checkbox_field'),
				'i2_azon_amazon',
				'i2_azon_section_amazon',
				['id' => 'use_amazon_api_key']
			);

			add_settings_field(
				'partner_tag', // ID
				'Enter your partner tag', // Title 
				array($this, 'i2_azon_callback_text_field'), // Callback
				'i2_azon_amazon', // Page
				'i2_azon_section_amazon', // Section           
				['id' => 'partner_tag', 'size' => 50, 'myclass' => $this->options['use_amazon_api_key'] == 1 ?  'i2azon-partner' : 'i2azon-partner i2azon-hidden', 'show_if_valid' => $this->options['api_key_connected']]
			);
			add_settings_field(
				'access_key', // ID
				'Enter your Access Key', // Title 
				array($this, 'i2_azon_callback_text_field'), // Callback
				'i2_azon_amazon', // Page
				'i2_azon_section_amazon', // Section           
				['id' => 'access_key', 'myclass' => $this->options['use_amazon_api_key'] == 1 ?  'i2azon-partner' : 'i2azon-partner i2azon-hidden', 'type' => 'password', 'size' => 50]
			);
			add_settings_field(
				'secret_key', // ID
				'Enter your Secret Key', // Title 
				array($this, 'i2_azon_callback_text_field'), // Callback
				'i2_azon_amazon', // Page
				'i2_azon_section_amazon', // Section           
				['id' => 'secret_key', 'myclass' => $this->options['use_amazon_api_key'] == 1 ?  'i2azon-partner' : 'i2azon-partner i2azon-hidden', 'type' => 'password', 'size' => 50]
			);
		}

		/**
		 * Sanitize each setting field as needed
		 *
		 * @param array $input Contains all settings fields as array keys
		 */
		public function sanitize($input)
		{
			$new_input = array();

			$new_input['amz_store'] = isset($input['amz_store']) ?  sanitize_text_field($input['amz_store'])  : 'com';
			$new_input['allow_browser_extention_to_update'] = isset($input['allow_browser_extention_to_update']) ?  intval($input['allow_browser_extention_to_update'])  : 0;
			$new_input['i2_azon_browser_key'] = isset($input['i2_azon_browser_key']) &&  strlen($input['i2_azon_browser_key']) > 10 ?  sanitize_text_field($input['i2_azon_browser_key']) : wp_generate_uuid4();

			if (isset($input['update_browser_key'])) {
				$new_input['i2_azon_browser_key'] = wp_generate_uuid4();
			}
			$new_input['max_allowed_browser_update_in_a_day'] = isset($input['max_allowed_browser_update_in_a_day']) && intval($input['max_allowed_browser_update_in_a_day']) > 0 ? intval($input['max_allowed_browser_update_in_a_day'])  : 50;

			$new_input['mobile_breakpoint'] = isset($input['mobile_breakpoint']) && intval($input['mobile_breakpoint']) > 500 ? intval($input['mobile_breakpoint'])  : 768;


			$new_input['partner_tag'] = isset($input['partner_tag']) ?  sanitize_text_field($input['partner_tag'])  : '';
			$new_input['access_key'] = isset($input['access_key']) ?   sanitize_text_field($input['access_key'])  : '';
			$new_input['secret_key'] = isset($input['secret_key']) ?   sanitize_text_field($input['secret_key'])  : '';
			$new_input['api_key_connected'] = $this->options['api_key_connected'];

			if (
				$new_input['partner_tag'] != $this->options['partner_tag'] ||
				$new_input['access_key'] != $this->options['access_key'] ||
				$new_input['secret_key'] != $this->options['secret_key'] ||
				$new_input['amz_store'] != $this->options['amz_store']
			) {

				//$api_direct = new ThemesFirst\Plugin\I2Azon\API_Search("id", array("B07P6FS6HW","B082YH2JJ4"));

				$api_direct = new API_Search("test", 'B082YH2JJ4');
				$api_direct->setKeysForValidate($new_input['amz_store'], $new_input['partner_tag'], $new_input['access_key'], $new_input['secret_key']);
				$jdata = $api_direct->get_items();
				$data = json_decode($jdata, true);
				if (array_key_exists('Errors', $data)) {
					$message = __($data['Errors'][0]['Message'], 'themesfirst');
					add_settings_error(
						'i2_azon',
						esc_attr('settings_updated'),
						$message,
						'error'
					);
					$new_input['api_key_connected'] = 0;
				} else {
					$new_input['api_key_connected'] = 1;
				}
				// var_dump(json_decode($data, true));

				// exit();

			}


			$new_input['partner_tag_for_link'] = isset($input['partner_tag_for_link']) && strlen($input['partner_tag_for_link']) > 1 ?   sanitize_text_field($input['partner_tag_for_link'])  : $new_input['partner_tag'];
			$new_input['partner_tag_donot_add_link'] = isset($input['partner_tag_donot_add_link']) ?   $input['partner_tag_donot_add_link']  : '';
			$new_input['schedule'] = isset($input['schedule']) ?   $input['schedule']  : '';

			$new_input['add_proxy_link'] = isset($input['add_proxy_link']) ?  intval($input['add_proxy_link'])  : 0;
			$new_input['proxy_link_slug'] = isset($input['proxy_link_slug']) ?  sanitize_text_field($input['proxy_link_slug'])  : $this->options['proxy_link_slug'];

			$new_input['add_proxy_image'] = isset($input['add_proxy_image']) ?  intval($input['add_proxy_image'])  : 0;
			$new_input['use_amazon_api_key'] = isset($input['use_amazon_api_key']) ?  intval($input['use_amazon_api_key'])  : 0;



			return $new_input;
		}

		/** 
		 * Print the Section text
		 */
		public function print_section_info()
		{
			print 'you can use your browser key to add product from amazon store, check how you can use it <a href="https://themesfirst.com/i2-azon-browser-extention/" target="_blank">Themesfirst</a>, Download extention from <a href="https://chrome.google.com/webstore/detail/i2-azon/lbfdobgmnjmaffigfgljoalofenedpbm?hl=en" target="_blank">chrome store</a>';
		}

		public function print_amazon_section_info()
		{
			print 'Enter your amazon keys and update settings below:';
		}

		public function i2_azon_browser_key_callback()
		{
			if (isset($this->options['i2_azon_browser_key'])) {
				echo $this->options['i2_azon_browser_key'];
			}
		}


		function i2_azon_callback_text_field($args)
		{

			$id    = isset($args['id'])    ? $args['id']    : '';
			$class = isset($args['myclass']) ? $args['myclass'] : '';
			$disabled = isset($args['disabled']) ? ' disabled="' . $args['disabled'] . '"' : '';
			$onclick = isset($args['onclick']) ? ' onclick="' . $args['onclick'] . '"' : '';
			$size = isset($args['size']) ? $args['size'] : '40';
			$helptext = isset($args['helptext']) ? '<p class="description">' . $args['helptext'] . '</p>' : '';
			$suffix = isset($args['suffix']) ? '<span class="suffix">' . $args['suffix'] . '</span>' : '';
			$type = isset($args['type']) ? $args['type'] : 'text';
			$value = isset($this->options[$id]) ? sanitize_text_field($this->options[$id]) : '';
			$show_if_valid = isset($args['show_if_valid']) && $args['show_if_valid'] == 1 ? true : false;

			if ($type == 'password') {
				$helptext = " <a href='#' class='tf-show-hidden-field' data-id='i2_azon_options_{$id}' >Show</a> &nbsp; " . $helptext;
			}

			if ($show_if_valid) {
				$helptext = " <i class='dashicons dashicons-yes-alt' style='color:green'></i> " . __('Connected', 'themesfirst') . " &nbsp; " . $helptext;
			}

			echo '<input id="i2_azon_options_' . $id . '" name="i2_azon_options[' . $id . ']" class="' . $class . '"' . $disabled . $onclick . ' type="' . $type . '" size="' . $size . '" value="' . $value . '" /> ' . $suffix . $helptext;
		}

		function i2_azon_callback_select_field($args)
		{
			$id    = isset($args['id'])    ? $args['id']    : '';
			$disabled    = isset($args['disabled'])    ? $args['disabled']    : '';
			$data_function    = isset($args['data_function'])    ? $args['data_function']    : 'i2_azon_get_stores';


			$selected_option = isset($this->options[$id]) ? sanitize_text_field($this->options[$id]) : 'default';

			$select_options = method_exists($this, $data_function) ? $this->$data_function() :  $this->i2_azon_get_stores();

			echo '<select id="i2_azon_options_' . $id . '" name="i2_azon_options[' . $id . ']" ' . $disabled . '>';

			foreach ($select_options as $value => $option) {

				$selected = selected($selected_option === $value, true, false);

				echo '<option value="' . $value . '"' . $selected . '>' . $option . '</option>';
			}

			echo '</select>';
		}
		function i2_azon_callback_checkbox_field($args)
		{

			$id    = isset($args['id'])    ? $args['id']    : '';
			$checked = isset($this->options[$id]) ? checked($this->options[$id], 1, false) : '';
			$helptext = isset($args['helptext']) ? '<p class="description">' . $args['helptext'] . '</p>' : '';

			echo '<input id="i2_azon_options_' . $id . '" name="i2_azon_options[' . $id . ']" type="checkbox" value="1"' . $checked . '/>' . $helptext;
		}

		function i2_azon_get_stores()
		{

			$amz_stores = array(
				'com'    => __('US', 'themesfirst'),
				'co.uk'  => __('UK', 'themesfirst'),
				'in'     => __('India', 'themesfirst'),
				'com.au' => __('Australia', 'themesfirst'),
				'com.br' => __('Brazil', 'themesfirst'),
				'ca'     => __('Canada', 'themesfirst'),
				// 'cn'     => __('China', 'themesfirst'),
				'fr'     => __('France', 'themesfirst'),
				'de'     => __('Germany', 'themesfirst'),
				'it'     => __('Italy', 'themesfirst'),
				'co.jp'  => __('Japan', 'themesfirst'),
				'com.mx' => __('Mexico', 'themesfirst'),
				'nl'     => __('Netherlands', 'themesfirst'),
				'sg'     => __('Singapore', 'themesfirst'),
				'sa'     => __('Saudi Arabia', 'themesfirst'),
				'es'     => __('Spain', 'themesfirst'),
				'com.tr' => __('Turkey', 'themesfirst'),
				'ae'     => __('United Arab Emirates', 'themesfirst')
			);

			return $amz_stores;
		}

		function get_schedule_timing()
		{

			$amz_schedule = array(
				''  => __('Select cache duration', 'themesfirst'),
				'12h' => __('12 Hours', 'themesfirst'),
				'24h' => __('Daily', 'themesfirst'),
				'3d' => __('3 Days', 'themesfirst'),
				'week'     => __('Weekly', 'themesfirst'),
				'byweek'     => __('Byweekly', 'themesfirst'),
			);

			return $amz_schedule;
		}

		function isValidDate($date, $format = 'Y/m/d')
		{
			$d = \DateTime::createFromFormat($format, $date);
			return $d && $d->format($format) === $date;
		}
	}
}

//if (current_user_can('manage_options'))
$i2_azon_settings_page = new i2_AZON_Settings_Page();
