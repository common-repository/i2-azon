<?php
namespace ThemesFirst\Plugin\I2Azon;
class i2_AZON_Db_Search
{
    protected $asins;
    protected $is_detail;
    protected $table_name;

    public function __construct($asins, $is_detail = false)
    {
        $this->asins = $asins;
        $this->is_detail = $is_detail;
        $this->set_table_name();
    }

    function get_search_results($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        //LIMIT 5,20
        //print_r($data); exit;
        $limit = 10;
        $start = intval($data['page']) * $limit;


        $select_sql = "SELECT asin, image_ids, title, brand, manufacturer, model, is_amazon_fulfilled, is_prime_eligible, display_price, saving_amount, CAST(rating AS DECIMAL(3,2)) as rating, reviews ";
        $where = strlen($data['term']) == 0 ? " FROM {$table_name} "  :  " FROM {$table_name} WHERE `title` like '%%%s%%' || `brand` like '%%%s%%' || `asin` = '%s'";
        $sql = $select_sql . $where . " ORDER BY date_updated DESC LIMIT {$start}, {$limit}";
        // echo $sql;

        $result = array();
        if (strlen($data['term']) == 0) {
            $result['SearchResult']['Items'] =  $wpdb->get_results($sql, 'ARRAY_A');
            $result['SearchResult']['TotalResultCount'] =  $wpdb->get_var("Select Count(*) " . $where);
        } else {
            $result['SearchResult']['Items'] =  $wpdb->get_results($wpdb->prepare(
                $sql,
                $wpdb->esc_like($data['term']),
                $wpdb->esc_like($data['term']),
                $data['term']
            ), 'ARRAY_A');
            $result['SearchResult']['TotalResultCount'] =  $wpdb->get_var($wpdb->prepare(
                "Select Count(*) " . $where,
                $wpdb->esc_like($data['term']),
                $wpdb->esc_like($data['term']),
                $data['term']
            ));
        }
        return $result;
    }
    function get_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $sql = "SELECT * FROM {$table_name} WHERE `asin` IN(" . implode(', ', array_fill(0, count($this->asins), '%s')) . ")  ORDER BY FIELD(asin, '" . implode("', '", $this->asins) . "')";
        // echo $sql;

        // Call $wpdb->prepare passing the values of the array as separate arguments
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $this->asins));
        //return $query;
        return $wpdb->get_results($query, 'ARRAY_A');
    }
    function get_item()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $sql = "SELECT * FROM {$table_name} WHERE `asin` IN(" . implode(', ', array_fill(0, count($this->asins), '%s')) . ")";
        // echo $sql;

        // Call $wpdb->prepare passing the values of the array as separate arguments
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $this->asins));
        //return $query;
        return $wpdb->get_row($query, 'ARRAY_A');
    }
    function get_images()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $sql = "SELECT image_ids FROM {$table_name} WHERE `asin` IN(" . implode(', ', array_fill(0, count($this->asins), '%s')) . ")";
        // echo $sql;

        // Call $wpdb->prepare passing the values of the array as separate arguments
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $this->asins));
        //return $query;
        return $wpdb->get_row($query, 'ARRAY_A');
    }
    function get_content()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $sql = "SELECT title, features FROM {$table_name} WHERE `asin` IN(" . implode(', ', array_fill(0, count($this->asins), '%s')) . ")";
        // echo $sql;

        // Call $wpdb->prepare passing the values of the array as separate arguments
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $this->asins));
        //return $query;
        return $wpdb->get_row($query, 'ARRAY_A');
    }
    function get_contents()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $sql = "SELECT asin, image_ids, title, features, brand, manufacturer, model, is_amazon_fulfilled, is_prime_eligible, display_price, saving_amount, CAST(rating AS DECIMAL(3,2)) as rating, reviews FROM {$table_name} WHERE `asin` IN(" . implode(', ', array_fill(0, count($this->asins), '%s')) . ")";
        // echo $sql;

        // Call $wpdb->prepare passing the values of the array as separate arguments
        $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $this->asins));
        //return $query;
        return $wpdb->get_results($query, 'ARRAY_A');
    }

    function remove_item()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

       return $wpdb->delete( $table_name, array( 'asin' => $this->asins[0]) );
    }


    function set_table_name()
    {
        global $wpdb;
        $this->table_name  = $this->is_detail ?  $wpdb->prefix . 'i2_azon_items_details' :  $wpdb->prefix . 'i2_azon_items';
    }
    function is_exist($asin)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) from {$table_name} where asin = %s", $asin)) > 0;
    }
}
