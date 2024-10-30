<?php
/**
 * Plugin Name: i2 AZON
 * Plugin URI: https://wordpress.org/plugins/i2-azon
 * Description: Allow to add amazon affilate link in any text block, dispaly amazon product image and product box with amazon adervisting api or with out api.
 * Author: imibrar
 * Author URI: https://themesfirst.com/
 * Version: 0.2.5
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require plugin_dir_path(__FILE__) . 'include/class-db-setup.php';
//setup database table
register_activation_hook(__FILE__, array('I2_Azon_Setup', 'install'));

include_once ABSPATH . 'wp-admin/includes/plugin.php';

// check if i2 AZON Pro is active 
if (!is_plugin_active( 'i2-azon-pro/i2-azon-pro.php' ) ) {
	// i2 AZON Pro is not active

define('I2_AZON_PLUGIN_NAME', 'i2 AZON' );
define('I2_AZON_VER', '0.2.5' );
define('I2_AZON_DEBUG', false );
define('I2_AZON_BASE_FILE', __FILE__ );
define('I2_AZON_ROOT', dirname( plugin_basename( __FILE__) ) );
define('I2_AZON_DIR_PATH', plugin_dir_path(__FILE__));
define('I2_AZON_BASE_URL', plugin_dir_url(__FILE__));


/**
 * Include php files.
 */
require I2_AZON_DIR_PATH . 'include/init.php';
require I2_AZON_DIR_PATH . 'include/helper.php';

require I2_AZON_DIR_PATH . 'include/class-setting.php';
require I2_AZON_DIR_PATH . 'include/class-aws-v5.php';
require I2_AZON_DIR_PATH . 'include/class-api-search.php';

require I2_AZON_DIR_PATH . 'include/class-db-search.php';
require I2_AZON_DIR_PATH . 'include/class-product.php';

require I2_AZON_DIR_PATH . 'include/rest-api.php';
require I2_AZON_DIR_PATH . 'include/register-blocks.php';
require I2_AZON_DIR_PATH . 'include/register-post-types.php';
}