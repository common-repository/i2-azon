<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function i2_azon_admin_script()
{
        //replace with your page "id"
    if(isset($_GET["page"])  && $_GET["page"] == "i2_azon_setting_page")
    {
        wp_enqueue_script("i2-azon-setting-admin-js", I2_AZON_BASE_URL . "dist/js/admin.js");
    }
}

add_action("admin_enqueue_scripts", "i2_azon_admin_script");




add_filter('plugin_action_links_' . plugin_basename(I2_AZON_BASE_FILE), 'i2_azon_page_settings_link');
function i2_azon_page_settings_link($links)
{
    $links[] = '<a href="' .
        admin_url('admin.php?page=i2_azon_setting_page') .
        '">' . __('Settings', 'themesfirst') . '</a>';
    return $links;
}


add_action('init', 'i2azon_redirect');
function i2azon_redirect()
{
    $azon_options = get_option('i2_azon_options', ThemesFirst\Plugin\I2Azon\i2_azon_options_default());

    if($azon_options['add_proxy_link'] == 1 && strpos(urldecode($_SERVER['REQUEST_URI']), '/' . $azon_options['proxy_link_slug'] . '/') !== false ){
    // Remove the trailing slash if there is one
    //$request_uri = preg_replace('#/$#','',urldecode($_SERVER['REQUEST_URI']));
    $request_uri = urldecode($_SERVER['REQUEST_URI']);

    $pos = strpos($request_uri, '/' . $azon_options['proxy_link_slug'] . '/');
    $asin = substr($request_uri, $pos + strlen ($azon_options['proxy_link_slug']) + 2 , 10);

	if(isset($azon_options['amz_store'])){
        $url = $azon_options['partner_tag_donot_add_link'] != true ?
        "https://www.amazon.{$azon_options['amz_store']}/dp/{$asin}/?tag={$azon_options['partner_tag_for_link']}&linkCode=ogi&th=1&psc=1" : 
        "https://www.amazon.{$azon_options['amz_store']}/dp/{$asin}/";      
        if ( wp_redirect($url) ) {
            exit;
        }
    }
  }
}