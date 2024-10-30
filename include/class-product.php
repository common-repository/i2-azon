<?php
namespace ThemesFirst\Plugin\I2Azon;
class i2_AZON_Product
{
    protected $data;
    protected $db_data;
    protected $is_detail;
    protected $table_name;

    public function __construct($data, $is_detail = false)
    {
        $this->data = $data;
        $this->is_detail = $is_detail;
        $this->set_table_name();
    }

	function get_dalily_update_by_extention(){
		global $wpdb;
		$today = date("Y-m-d");
		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) from {$this->table_name} where added_by_extention = %f and DATE(date_created) = %s" , 1, $today));
	}	
	function insert_from_extention($item){
		global $wpdb;
		 //  print_r($item);
		 // $wpdb->show_errors();
		  $item4db = $this->get_column_defaults();
		  $this->db_data = array_merge($item4db, $item);
		  $result = $wpdb->insert($this->table_name, $this->db_data , $this->get_column_default_types());
		  // echo "\n";
		 // echo $wpdb->last_query;
		//   if($result == false){
		//       echo $wpdb->print_error();
		//       echo $wpdb->last_error;
		//       echo $wpdb->last_query;
		//   }
		  if($result != false){
			  $this->db_data['id'] =  $wpdb->insert_id;
		  }
		  return $result;
	}
    function insert(){
        global $wpdb;
      //  $wpdb->show_errors();
        $result = $wpdb->insert($this->table_name, $this->get_data_for_db(), $this->get_column_default_types());
        // echo $wpdb->last_query;
        // if($result == false){
        //     echo $wpdb->print_error();
        //     echo $wpdb->last_error;
        //     echo $wpdb->last_query;
        // }
        if($result != false){
            $this->db_data['id'] =  $wpdb->insert_id;
        }
        return $result;
    }
    function get_newly_added_item()
    {
        return $this->db_data;
    }

    function get_column_defaults()
    {
        return array(
            'status' => 'active',
            'asin' => '',
            'product_group' => '',
            'title' => '',
            'url' => '',
            'brand' => '',
            'features' => '',
            'manufacturer' => '',
            'item_part_number' => '',
            'model' => '',
            'contributors' => '',
            'content_info' => '',
            'salesrank' => '',
            'image_ids' => '',

            'availability' => '',
            'conditions' => '',
            'is_amazon_fulfilled' => 0,
            'is_prime_eligible' => 0,

            'price' => 0,
            'display_price' => '',
            'currency' => '',
            'saving_percentage' => '',
            'saving_amount' => '',

            'rating' => 0,
            'reviews' => 0,
            'is_modified' => 0,
            'reviews_updated' => date('Y-m-d H:i:s'),
            'date_created' => date('Y-m-d H:i:s'),
						'date_updated' => date('Y-m-d H:i:s'),
						'added_by_extention' => 0
        );
    }

    function get_column_default_types()
    {
        return array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%f');
    }
    function set_table_name(){
        global $wpdb;
        $this->table_name  = $this->is_detail?  $wpdb->prefix . 'i2_azon_items_details' :  $wpdb->prefix . 'i2_azon_items';
    }
    function get_data_for_db(){
           $this->db_data = $this->prepare_item();
        //    if(function_exists('i2_print_it')){
        //     i2_print_it($this->db_data);
        //  //   i2_print_it($this->data);
        //    }else{
        //        echo "i2_print_it not exist";
        //    }
           return $this->db_data;
    }

    function prepare_item(){

        $review = $this->get_review();
        $item4db = $this->get_column_defaults();
        $item4db['asin'] = $this->data['ASIN'];
        $item4db['product_group'] = $this->data['ItemInfo']['Classifications']['ProductGroup']['DisplayValue'];
        $item4db['title'] = $this->data['ItemInfo']['Title']['DisplayValue'];
        $item4db['url'] = $this->data['DetailPageURL'];
        $item4db['brand'] = $this->data['ItemInfo']['ByLineInfo']['Brand']['DisplayValue'];

        if (array_key_exists('Contributors', $this->data['ItemInfo']['ByLineInfo'])) {
            $item4db['contributors'] = json_encode($this->prepare_data($this->data['ItemInfo']['ByLineInfo']['Contributors']));
        }
        //print_it($item['ItemInfo']['ContentInfo']);
        if (array_key_exists('ContentInfo', $this->data['ItemInfo'])) {
            $item4db['content_info'] = json_encode($this->prepare_data($this->data['ItemInfo']['ContentInfo']));
        }

        $item4db['features'] = json_encode($this->data['ItemInfo']['Features']['DisplayValues']);
        $item4db['manufacturer'] = $this->data['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'];
        $item4db['item_part_number'] = $this->data['ItemInfo']['ManufactureInfo']['ItemPartNumber']['DisplayValue'];
        if (isset($this->data['ItemInfo']['ManufactureInfo']) && array_key_exists('Model', $this->data['ItemInfo']['ManufactureInfo'])) {
            $item4db['model'] = $this->data['ItemInfo']['ManufactureInfo']['Model']['DisplayValue'];
        }
        if (isset($this->data['BrowseNodeInfo']['BrowseNodes'][0]['SalesRank'])) {
            $item4db['salesrank'] = $this->data['BrowseNodeInfo']['BrowseNodes'][0]['SalesRank'];
        }
        $item4db['image_ids'] = json_encode($this->get_images($this->data['Images']));
        if (isset($this->data['Offers'])) {
            $item4db['availability'] = $this->data['Offers']['Listings'][0]['Availability']['Message'];
            $item4db['conditions'] = $this->data['Offers']['Listings'][0]['Condition']['Value'];
            $item4db['is_amazon_fulfilled'] = $this->data['Offers']['Listings'][0]['DeliveryInfo']['IsAmazonFulfilled'];
            $item4db['is_prime_eligible'] = $this->data['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'];

            $item4db['price'] = $this->data['Offers']['Listings'][0]['Price']['Amount'];
            $item4db['display_price'] = $this->data['Offers']['Listings'][0]['Price']['DisplayAmount'];
            $item4db['currency'] = $this->data['Offers']['Listings'][0]['Price']['Currency'];
            if (isset($this->data['Offers']['Listings'][0]['Price']['Savings'])) {
                $item4db['saving_percentage'] = $this->data['Offers']['Listings'][0]['Price']['Savings']['Percentage'];
                $item4db['saving_amount'] = $this->data['Offers']['Listings'][0]['Price']['Savings']['Amount'];
            }
        }

        if (is_array($review) && array_key_exists('rating', $review)) {
            $item4db['rating'] =   $review['rating'];
            $item4db['reviews'] =   $review['reviews'];
            $item4db['reviews_updated'] =   date('Y-m-d H:i:s');
        }
        $item4db['date_created'] =   date('Y-m-d H:i:s');
        $item4db['date_updated'] =   date('Y-m-d H:i:s');

        return $item4db;
    }


    function prepare_data($obj)
    {
        $resutl = array();
        if ($this->is_final($obj)) {
            $resutl[$this->get_key($obj)] = $this->get_value($obj);
        } else {
            foreach ($obj as $sub) {
                if ($this->is_final($sub)) {
                    $resutl[$this->get_key($sub)] = $this->get_value($sub);
                } else {
                    foreach ($sub as $child) {
                        if ($this->is_final($child)) {
                            $resutl[$this->get_key($child)] = $this->get_value($child);
                            if (isset($child['Unit']) && !isset($resutl['DimensionsUnit']) && $this->is_dimension($child)) {
                                $resutl['DimensionsUnit'] = $child['Unit'];
                            }
                        }
                    }
                }
            }
        }
        return $resutl;
    }
    function get_key($obj)
    {
        if (isset($obj['Label'])) {
            return !ctype_upper($obj['Label']) ? trim(preg_replace('/(?<!\ )[A-Z]/', ' $0',  $obj['Label'])) : $obj['Label'];
        }
        if (isset($obj['Role'])) {
            return !ctype_upper($obj['Role']) ? trim(preg_replace('/(?<!\ )[A-Z]/', ' $0',  $obj['Role'])) : $obj['Role'];
        }
        return false;
    }
    function get_value($obj)
    {
        if (isset($obj['DisplayValue']) && isset($obj['Unit']) && !$this->is_dimension($obj)) {
            return  $this->get_clean_value($obj['DisplayValue']) . ' ' .   $obj['Unit'];
        }
        if (isset($obj['DisplayValue'])) {
            return  $this->get_clean_value($obj['DisplayValue']);
        }
        if (isset($obj['Name'])) {
            return  $obj['Name'];
        }
        if (isset($obj['DisplayValues'])) {
            if (is_array($obj['DisplayValues'][0])) {
                $data = array();
                foreach ($obj['DisplayValues'] as $val) {
                    if (array_key_exists($val['Type'], $data)) {
                        $data[$val['Type']] .= ', ' . $this->get_value($val);
                    } else {
                        $data[$val['Type']] =  $this->get_value($val);
                    }
                }
                return $data;
            } else {
                if (isset($obj['Label']) && $obj['Label']  == "Features") {
                    return $obj['DisplayValues'];
                } else {
                    return join(', ', $obj['DisplayValues']);
                }
            }
        }

        return false;
    }

    function get_clean_value($obj)
    {
        if (is_numeric($obj) && floor($obj) != $obj) {
            return number_format($obj, 2);
        }
        return $obj;
    }

    function is_final($obj)
    {
        return isset($obj['Label']) || isset($obj['Role']);
    }
    function is_dimension($obj)
    {
        $d_label = array('Height', 'Length', 'Width');
        return in_array($obj['Label'], $d_label);
    }

    function get_review(){
          //pro version feature   
        // $reviewCrowler = new i2_AZON_Review_Grabber();
           // return $reviewCrowler ->get_data($this->data['ASIN']);
           return array();

    }

    function get_images($images)
{

    if ($images) {

        $image_urls = array();

        // Primary image
        if (isset($images['Primary']) && $images['Primary']['Large'] && !empty($images['Primary']['Large']['URL']))
            $image_urls[] = $images['Primary']['Large'];

        // Variants
        if (isset($images['Variants']) && is_array($images['Variants']) && sizeof($images['Variants']) > 0) {

            foreach ($images['Variants'] as $image_variant) {

                if (isset($image_variant['Large']) && !empty($image_variant['Large']['URL']) && !in_array($image_variant['Large']['URL'], $image_urls))
                    $image_urls[] = $image_variant['Large'];
            }
        }

        // $image_ids = aawp_get_product_image_ids_from_urls( $image_urls, true );

        // if ( ! empty ( $image_ids ) )
				//     $data['image_ids'] = $image_ids;
        return  $this->clean_images($image_urls);
    }
}

function clean_images($image_urls)
{

    if (empty($image_urls) || !is_array($image_urls) || sizeof($image_urls) === 0)
        return null;

    $images = array();

		//https://www.amazon.com/gp/help/customer/display.html?nodeId=201995030
		
    $image_search_replace = array(
        'https://m.media-amazon.com/images/I/' => '', // media CDN
        '.jpg' => '', //  file extension  
        '.jpeg' => '', //  file extension
        '.png' => '', //  file extension
        '.gif' => '', //  file extension
        '.tiff' => '', //  file extension
        '._SL500_' => '' // No needed filename string
    );

    foreach ($image_urls as $image_url) {

        if (empty($image_url))
            continue;

				$image_id = strtr($image_url['URL'], $image_search_replace);
				$image_ext =  pathinfo($image_url['URL'], PATHINFO_EXTENSION);
				$image_url['URL'] =   $image_id;
				$image_url['EXT'] =  $image_ext;
        if (!empty($image_id) && !in_array($image_url, $images))
            $images[] = $image_url;
    }

		// echo '<pre>'; 
		//  print_r($images); 
		//  echo '</pre>';
		//  exit();
    return $images;
}

function get_contributors($arr)
{
    $obj = array();
    if (is_array($arr)) {
        foreach ($arr as $item) {
            $obj[] = array('role' => $item['Role'], 'name' => $item['Name']);
        }
    }
    return $obj;
}
function get_content_info($arr)
{
    $obj = array();
    if (is_array($arr['ContentInfo'])) {
        if (array_key_exists('Edition', $arr['ContentInfo'])) {
            $obj[$arr['ContentInfo']['Edition']['Label']] = $arr['ContentInfo']['Edition']['DisplayValue'];
        }
        if (array_key_exists('Languages', $arr['ContentInfo'])) {
            $subObj = array();
            $hasSubtitle = false;
            $hasOriginal = false;

            foreach ($arr['ContentInfo']['Languages']['DisplayValues'] as $item) {
                if ($item['Type'] == 'Subtitled') {
                    $subObj[$item['Type']] .= ($hasSubtitle == true ? ", " : "") . $item['DisplayValue'];
                    $hasSubtitle = true;
                } else if ($item['Type'] == 'Original Language') {
                    $subObj[$item['Type']] .= ($hasOriginal == true ? ", " : "") . $item['DisplayValue'];
                    $hasOriginal = true;
                }
            }
            $obj[$arr['ContentInfo']['Languages']['Label']] = $subObj;
        }
    }
    if (array_key_exists('ContentRating', $arr)) {
        $obj[$arr['ContentRating']['AudienceRating']['Label']] = $arr['ContentRating']['AudienceRating']['DisplayValue'];
    }
    // print_it($obj);
    return $obj;
}
}
