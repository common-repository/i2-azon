<?php

add_action('rest_api_init', 'i2azon_rest_api_setup');

function i2azon_rest_api_setup () {
	//global $wp_version;
	if (current_user_can('edit_posts')) {
		register_rest_route('i2-azon/v1', '/product/(?P<asin>[A-Za-z0-9-]{10})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_load_single_api',
			'permission_callback' => '__return_true'
		));
		register_rest_route('i2-azon/v1', '/images/(?P<asin>[A-Za-z0-9-]{10})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_load_iamges_api',
			'permission_callback' => '__return_true'
		));
		register_rest_route('i2-azon/v1', '/content/(?P<asin>[A-Za-z0-9-]{10})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_load_content_api',
			'permission_callback' => '__return_true'
		));
		register_rest_route('i2-azon/v1', '/contents/(?P<asin>[A-Za-z0-9-,]{10,110})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_load_contents_api',
			'permission_callback' => '__return_true'
		));
		register_rest_route('i2-azon/v1', '/search/(?P<term>[\w \d%20]{0,110})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_load_search_api',
			'permission_callback' => '__return_true'
		));
		register_rest_route('i2-azon/v1', '/delete/(?P<asin>[A-Za-z0-9-]{10})', array(
			'methods'  => 'GET',
			'callback' => 'i2azon_delete_content_api',
			'permission_callback' => '__return_true'
		));
	}
	register_rest_route('i2-azon/v1', '/add_amz_product/(?P<asin>[A-Za-z0-9-]{10})', array(
		'methods'  => 'POST',
		'callback' => 'i2azon_submit_single_porduct_api',
		'permission_callback' => '__return_true'
	));
}


function i2azon_load_search_api($request)
{
	$term =  urldecode($request['term']);
	$term = sanitize_text_field($term);

	$si = urldecode($request['si']);
	$si = sanitize_text_field($si);

	$page = intval($request['page']);

	$sortBy =  urldecode($request['sortBy']);
	$sortBy = sanitize_text_field($sortBy);

	$sourceRemote = $request['sourceRemote'];

	$data = array(
		'term' => $term == "NOTERM" ? "" : $term,
		'sortBy' => $sortBy,
		'searchIndex' => $si,
		'page' => $page,
	);

	if ($sourceRemote == 'true') {
		$api_direct = new ThemesFirst\Plugin\I2Azon\API_Search("search", $data);
		$results = $api_direct->get_items();
		echo $results;
		exit();
	} else {
		$db_direct = new ThemesFirst\Plugin\I2Azon\i2_AZON_Db_Search('');
		$db_result = $db_direct->get_search_results($data);
		echo json_encode($db_result, 0, 4);
		exit();
	}
}

function i2azon_load_contents_api($request)
{
	$asin = $request['asin'];

	$idsAr = preg_split('/\s*,\s*/', $asin);

	$db_items = new ThemesFirst\Plugin\I2Azon\i2_AZON_Db_Search($idsAr);
	$db_results = $db_items->get_contents();

	if (count($db_results) !== count($idsAr)) {
		$not_exist_ids = $idsAr;

		foreach ($db_results as $db_item) {
			if (($key = array_search($db_item['asin'], $not_exist_ids)) !== false) {
				unset($not_exist_ids[$key]);
			}
		}

		$i2_api = new ThemesFirst\Plugin\I2Azon\API_Search('id', $not_exist_ids);
		$api_results = json_decode($i2_api->get_items(), true);

		if (isset($api_results['ItemsResult']) && isset($api_results['ItemsResult']['Items']) && count($api_results['ItemsResult']['Items']) > 0) {
			foreach ($api_results['ItemsResult']['Items'] as $item) {
				$i2_item = new ThemesFirst\Plugin\I2Azon\i2_AZON_Product($item);
				$result = $i2_item->insert();
				if ($result !== false) {
					//	$db_results = $i2_item->get_newly_added_item();
					array_push($db_results, $i2_item->get_newly_added_item());
				}
			}
		} else {
			echo json_encode(array('Errors' =>  'no record found'));
			exit();
		}
	}

	echo json_encode($db_results);
	exit();
}

function i2azon_delete_content_api($request)
{
	$asin = $request['asin'];

	$idsAr = preg_split('/\s*,\s*/', $asin);
	$db_items = new ThemesFirst\Plugin\I2Azon\i2_AZON_Db_Search($idsAr);
	$db_results = $db_items->remove_item();
	echo json_encode($db_results);
	exit();
}

function i2azon_load_iamges_api($request)
{
	$asin = $request['asin'];
	$db_items = new ThemesFirst\Plugin\I2Azon\i2_AZON_Db_Search(array($asin));
	$db_results = $db_items->get_images();
	echo json_encode($db_results['image_ids']);
	exit();
}


function i2azon_submit_single_porduct_api($request)
{
	$key = sanitize_text_field($request['key']);
	$validate = ThemesFirst\Plugin\I2Azon\i2azon_is_allow_to_post_from_extention($key);
	if ($validate == "OK") {
		$asin = $request['asin'];

		$db_items = new ThemesFirst\Plugin\I2Azon\i2_AZON_Db_Search(array($asin));
		if ($db_items->is_exist($asin)) {
			echo json_encode(array('error' => true, 'msg' =>  '"' . $asin . '" is already exist in the database'));
		} else {
			$item['title'] = sanitize_text_field($_POST['title']);
			$item['price'] = sanitize_text_field($_POST['price']);
			$item['display_price'] = sanitize_text_field($_POST['displayPrice']);
			$item['currency'] = sanitize_text_field($_POST['currencySymble']);
			$item['saving_percentage'] = sanitize_text_field($_POST['savingPercentage']);
			$item['saving_amount'] = sanitize_text_field($_POST['savingAmount']);
			$item['rating'] = floatval($_POST['rating']);
			$item['reviews'] = intval($_POST['review']);
			$item['is_prime_eligible'] = sanitize_text_field($_POST['isPrime']);

			$item['asin'] = sanitize_text_field($_POST['asin']);

		   // $images = sanitize_text_field($_POST['images']);
		   // prepare images
			$item['image_ids'] = ThemesFirst\Plugin\I2Azon\i2azon_prepare_images($_POST['images']);

			//$features = sanitize_text_field($_POST['features']);
			// prepare features
			$item['features'] = ThemesFirst\Plugin\I2Azon\i2azon_prepare_features($_POST['features']);

			$item['added_by_extention'] = 1;
			$i2_item = new ThemesFirst\Plugin\I2Azon\i2_AZON_Product(null);
			$result = $i2_item->insert_from_extention($item);
			if ($result !== false) {
				echo json_encode(array('error' => false, 'msg' =>  '"' . $asin . '" is added succfully'));
			} else {
				echo json_encode(array('error' => true, 'msg' =>  '"' . $asin . '" could not saved, Please try again'));
			}
		}
	} else {
		echo json_encode(array('error' => true, 'msg' =>  $validate));
	}
	// $db_results = $db_items->get_item();
	// 	print_r($asin);
	// echo "\n=========================\n";
	// 	print_r($_POST);
	exit();
}
