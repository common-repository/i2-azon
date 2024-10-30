<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class I2_Azon_Setup
{
    static function install()
    {

        $i2_azon_db_version = '1.0';
        I2_Azon_Setup::setup_item();
      //  I2_Azon_Setup::setup_item_single();
				//print_r(dbDelta($sql));
        // exit();
        add_option('i2_azon_db_version', $i2_azon_db_version);
    }

    static function setup_item(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'i2_azon_items';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                status varchar(20),
                asin varchar(20) NOT NULL,
                product_group varchar(500),
                title text,
                brand varchar(500),
                url longtext,
                features longtext,
                contributors longtext,
                content_info longtext,
                manufacturer  varchar(500),
                item_part_number varchar(500),
                model varchar(500),
                salesrank bigint(10),
                image_ids longtext,

                availability varchar(200),
                conditions varchar(200),
                is_amazon_fulfilled tinyint(1),
                is_prime_eligible tinyint(1),

                price varchar(50),
                display_price varchar(50),
                currency varchar(10),
                saving_percentage tinyint(3),
                saving_amount varchar(50),

                rating varchar(10),
                reviews bigint(20),
                reviews_updated datetime,
                date_created datetime NOT NULL,
                date_updated datetime NOT NULL,
                is_modified tinyint(1),
								added_by_extention tinyint(1) NULL DEFAULT 0,

                PRIMARY KEY  (id),
                UNIQUE KEY asin (asin),
                KEY status (status),
                KEY reviews (reviews),
                KEY reviews_updated (reviews_updated),
                KEY date_updated (date_updated)
                ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		// echo $sql;
		// print_r(dbDelta($sql));
    //     exit();
        dbDelta($sql);
    }

}
