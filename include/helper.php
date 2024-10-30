<?php
namespace ThemesFirst\Plugin\I2Azon;

if( !defined( 'ABSPATH' ) ) exit;


function i2azon_prepare_images($obj){
	$images = array();
	foreach($obj as $rawimg){
		$sImg = explode(':', $rawimg);
		if(count($sImg) == 3){
	  $path_info = pathinfo($sImg[0]);
		 $images[] = array("Height" =>intval($sImg[2]),"URL"=>$path_info['filename'],"Width" => intval($sImg[1]), "EXT" => $path_info['extension']);
		}
	}
	//var_dump($images);
	return json_encode($images);
}

function i2azon_prepare_features($obj){	
	$features = array();
   if(is_array($obj)){
	foreach($obj as $raw){
		 $features[] = sanitize_text_field($raw);
		}
	}
	//var_dump($obj);
	return json_encode($features);
}

function i2azon_is_allow_to_post_from_extention($key){
	if(strlen($key) == 0){
		return __("key is not provided", 'themesfirst');
	}

	$options = get_option('i2_azon_options', i2_azon_options_default());

	if($options['i2_azon_browser_key'] != $key){
		return __("key is not mached, plese update your browser key", 'themesfirst');
	}
	if($options['allow_browser_extention_to_update'] == 0){
		return __("The update is blocked from the website, please contact your website admin to allow it", 'themesfirst');
	}	 

	$i2_item = new i2_AZON_Product(null);
	if($options['max_allowed_browser_update_in_a_day'] <= 	$i2_item->get_dalily_update_by_extention()){
		return __("Your daily update limit ({$options['max_allowed_browser_update_in_a_day']}) exceeded, Please update form plugin if you want to increase it.", 'themesfirst');
	}

	return "OK";
}

function i2azon_get_amazon_link($asin){
	$azon_options = get_option('i2_azon_options', i2_azon_options_default());	
	//https://www.amazon.com/dp/B00OJCDJGW?tag=tag20-20&linkCode=ogi&th=1&psc=1
	if(isset($azon_options['amz_store']) && !empty($azon_options['amz_store'])){

		if($azon_options['add_proxy_link'] == true){
			return home_url() . "/" .  $azon_options['proxy_link_slug']  . '/' . $asin; 
		} else
		{
			if( $azon_options['partner_tag_donot_add_link'] != true && !empty($azon_options['partner_tag_for_link']) ){
				return "https://www.amazon.{$azon_options['amz_store']}/dp/{$asin}/?tag=" . $azon_options['partner_tag_for_link'] ."&linkCode=ogi&th=1&psc=1" ;
			}else{
			return "https://www.amazon.{$azon_options['amz_store']}/dp/{$asin}/";
			}
	  	}
	}
	return "#";
}
