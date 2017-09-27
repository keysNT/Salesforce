<?php
/**
 * Plugin Name: WooCommerce Salesforce CRM Integration
 * Plugin URI: https://store.magenest.com/
 * Description: Sync data between woocommerce and salesforce
 * Author: Magenest
 * Version: 1.2
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (! defined ('SALESFORCE_TEXT_DOMAIN'))
    define ( 'SALESFORCE_TEXT_DOMAIN', 'hn_salesforce_integration' );

// Plugin Folder Path
if (! defined ('HNSALESFORCE_PATH'))
    define ('HNSALESFORCE_PATH', plugin_dir_path ( __FILE__ ) );

// Plugin Folder URL
if (! defined ('HNSALESFORCE_URL'))
    define ('HNSALESFORCE_URL', plugins_url ( 'woocommerce-salesforce-integration', 'woocommerce-salesforce-integration.php' ) );

// Plugin Root File
if (! defined ('HNSALESFORCE_FILE'))
    define ('HNSALESFORCE_FILE', plugin_basename ( __FILE__ ) );

class HN_Salesforce_Integration {

    private static $hnsalesforce_instance;

    /** plugin version number */
    const VERSION = '1.2';

    /** plugin text domain */
    const TEXT_DOMAIN = 'hn_salesforce_integration';

    public  $logger ;

    public function __construct(){
        global $wpdb;
        register_activation_hook(HNSALESFORCE_FILE, array($this, 'install'));
        add_action ( 'init', array ($this,'load_domain' ), 1 );
        add_filter ( 'woocommerce_get_settings_pages', array ($this,'add_settings_page' ), 10, 1 );
        $this->include_for_frontend();
        if(is_admin()){
            add_action('admin_init', array($this, 'sync_admin_init'), 5);
            add_action('admin_menu', array($this, 'create_admin_menu'), 5);
            add_action ( 'admin_enqueue_scripts', array ($this,'load_admin_scripts' ), 99 );
        }
    }
    public function sync_admin_init(){
        add_action('admin_post_sync_order', array($this,'sync_queue_order'));
        add_action('admin_post_sync_product', array($this, 'sync_queue_product'));
        add_action('admin_post_sync_lead', array($this,'sync_queue_lead'));
        add_action('admin_post_sync_account', array($this,'sync_queue_account'));
        add_action('admin_post_sync_contact', array($this,'sync_queue_contact'));//sync_contact
    }
    public function sync_queue_order(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_order';
        if(isset($_POST['btnSubmit']) && isset($_POST['order_id'])){
            $order_id = isset($_POST['order_id'])?$_POST['order_id']:0;
            if($order_id != 0){
                $order = wc_get_order($order_id);
                // get standard pricebook id
                $query = "select Id from Pricebook2 where isStandard = true";
                $response = hnsfQuery( $query );
                $records = $response->records;
                $records = $records[0];
                $pricebook_id = $records->Id;

                $billing_email = $order->billing_email;
                $int = strpos($billing_email,'@');
                $name = substr($billing_email,0,$int);
                $user_login = $name;

                $query = "select id, name from Account where name = '" . $user_login . "' AND phone = '" . $order->billing_phone . "'";
                $response = hnsfQuery( $query );
                if(empty($response)){
                    insert_account($order_id, $load_address = '');
                    insert_contact($order_id, $load_address = '');
                    insert_order($order_id);
                }else{
                    $records = $response->records;
                    $records = $records[0];
                    $account_id = $records->Id;
                    $order_data['EffectiveDate'] = date( 'Y-m-d', strtotime( $order->post->post_date ) );
                    $order_data['AccountId'] = $account_id;
                    $order_data['BillingCity'] = $order->billing_city;
                    $order_data['BillingState'] = $order->billing_state;
                    $order_data['BillingPostalCode'] = $order->billing_postcode;
                    $order_data['BillingCountry'] = $order->billing_country;
                    $order_data['ShippingCity'] = $order->shipping_city;
                    $order_data['ShippingState'] = $order->shipping_state;
                    $order_data['ShippingPostalCode'] = $order->shipping_postcode;
                    $order_data['ShippingCountry'] = $order->shipping_country;
                    $order_data['Status'] = "Draft";
                    $order_data['Pricebook2Id'] = $pricebook_id;

                    $param = json_encode( $order_data );
                    $orderId = hnsfInsert('Order', $param);
                    $data['salesforce_id']= $orderId;
                    $data['status'] = 0;
                    //$product_data['product_id']
                    foreach ($order->get_items() as $item){
                        $a = $item->get_data();
                        $Product2Id = insert_product($a['product_id']);
                        /**
                         * insert PricebookEntry
                         */
                        $PricebookEntry_data['Product2Id'] = $Product2Id;
                        $PricebookEntry_data['Pricebook2Id'] = $pricebook_id;
                        $PricebookEntry_data['UnitPrice'] = get_post_meta($item['product_id'], '_price', true );
                        $param = json_encode( $PricebookEntry_data );
                        $PricebookEntryId = hnsfInsert('PricebookEntry', $param);

                        $p = json_encode(
                            array(
                                'PricebookEntryId' => $PricebookEntryId,
                                'Quantity' => $item['quantity'],
                                'UnitPrice' => get_post_meta($item['product_id'], '_price', true ),
                                'OrderId' => $orderId
                            )
                        );
                        hnsfInsert('OrderItem', $p);
                    }
                }
                $wpdb->update($tbl, $data, array('order_id' => $order_id));
                wp_redirect(admin_url('admin.php?page=table_order'));
            }else{
                wp_redirect(admin_url('admin.php?page=table_order'));
            }
        }
    }
    public function sync_queue_lead(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_lead';
        if (isset($_POST['btnSubmit']) && isset($_POST['user_id'])){
            $user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
            if ($user_id != 0){
                $user_data = get_userdata ($user_id);
                $lead_data = array ();
                $lead_data['Company'] = 'NA';
                $lead_data['LastName'] = $user_data->data->display_name;
                $lead_data['Email'] = $user_data->data->user_email;

                $update_crm = array();
                foreach ($lead_data as $lead => $key){
                    $update_crm[] = array( 'field' => $lead, 'value' => $key);
                }
                $response = hnsfDuplicateItem( 'Lead', array( 'field' => 'Email', 'value' => $lead_data['Email'] ) );
                if($response->records[0]->Id){
                    $Id = $response->records[0]->Id;
                    $update = hnsfUpdate('Lead', $update_crm, $Id);
                    $data['salesforce_id']= $update;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_lead'));
                }else{
                    $param = json_encode( $lead_data );
                    $lead_id = hnsfInsert('Lead', $param);
                    $data['salesforce_id']= $lead_id;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_lead'));
                }
            }
        }
    }
    public function sync_queue_account(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_account';
        if(isset($_POST['btnSubmit']) && isset($_POST['user_id'])){
            $user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
            $crm_data = array();
            if ($user_id != 0){
                /** Billing Address **/
                $billing_street = get_user_meta($user_id,'billing_address_1','NA');
                $billing_city = get_user_meta($user_id,'billing_city','NA');
                $billing_state = get_user_meta($user_id,'billing_state','NA');
                $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
                $billing_country = get_user_meta($user_id, 'shipping_country','NA');
                $billing_email = get_user_meta($user_id,'billing_email','NA');
                $billing_phone = get_user_meta($user_id,'billing_phone','NA');

                /** Shipping Address **/
                $shipping_street = get_user_meta($user_id,'shipping_address_1','NA');
                $shipping_city = get_user_meta($user_id,'shipping_city','NA');
                $shipping_state = get_user_meta($user_id,'shipping_state','NA');
                $shipping_postcode  = get_user_meta($user_id,'shipping_postcode','NA');
                $shipping_country = get_user_meta($user_id,'shipping_country','NA');

                $int = strpos($billing_email,'@');
                $name = substr($billing_email,0,$int);
                $crm_data['Name'] = $name;
                $crm_data['Phone'] = $billing_phone;
                $crm_data['BillingStreet']  = $billing_street;
                $crm_data['BillingCity'] = $billing_city;
                $crm_data['BillingState'] = $billing_state;
                $crm_data['BillingPostalCode'] = $billing_postcode;
                $crm_data['BillingCountry'] = $billing_country;

                /** Shipping address */
                $crm_data['ShippingStreet'] = $shipping_street;
                $crm_data['ShippingCity'] = $shipping_city;
                $crm_data['ShippingState']  = $shipping_state;
                $crm_data['ShippingPostalCode'] = $shipping_postcode;
                $crm_data['ShippingCountry'] = $shipping_country;

                $update_crm = array();
                foreach ($crm_data as $crm => $key){
                    $update_crm[] = array( 'field' => $crm, 'value' => $key);
                }
                $response = hnsfDuplicateItem( 'Account', array( 'field' => 'name', 'value' => $crm_data['Name'] ) );
                if( ! empty( $response->records ) ) {
                    $Id = $response->records[0]->Id;
                    $update = hnsfUpdate('Account', $update_crm, $Id);
                    $data['salesforce_id']= $update;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_account'));
                }else{
                    $param = json_encode( $crm_data );
                    $account_id = hnsfInsert('Account', $param);
                    $data['salesforce_id']= $account_id;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_account'));
                }
            }
        }
    }
    public function sync_queue_contact(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_contact';
        if(isset($_POST['btnSubmit']) && isset($_POST['user_id'])) {
            $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
            $crm_data = array();
            if ($user_id != 0) {
                /** Billing Address **/
                $billing_street = get_user_meta($user_id,'billing_address_1','NA');
                $billing_city = get_user_meta($user_id,'billing_city','NA');
                $billing_state = get_user_meta($user_id,'billing_state','NA');
                $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
                $billing_country = get_user_meta($user_id, 'shipping_country','NA');
                $billing_email = get_user_meta($user_id,'billing_email','NA');
                $billing_phone = get_user_meta($user_id,'billing_phone','NA');

                /** Shipping Address **/
                $shipping_street = get_user_meta($user_id,'shipping_address_1','NA');
                $shipping_city = get_user_meta($user_id,'shipping_city','NA');
                $shipping_state = get_user_meta($user_id,'shipping_state','NA');
                $shipping_postcode  = get_user_meta($user_id,'shipping_postcode','NA');
                $shipping_country = get_user_meta($user_id,'shipping_country','NA');

                $int = strpos($billing_email,'@');
                $name = substr($billing_email,0,$int);
                $crm_data['Lastname'] = $name;
                $crm_data['Phone'] = $billing_phone;
                $crm_data['MobilePhone'] = $billing_phone;

                $crm_data['Email'] = $billing_email;
                $crm_data['MailingStreet'] = $billing_street;
                $crm_data['MailingCity'] = $billing_city;
                $crm_data['MailingState'] = $billing_state;
                $crm_data['MailingCountry'] = $billing_country;
                $crm_data['MailingPostalCode'] = $billing_postcode;

                $update_crm = array();
                foreach ($crm_data as $crm => $key){
                    $update_crm[] = array( 'field' => $crm, 'value' => $key);
                }
                $response = hnsfDuplicateItem('Contact', array( 'field' => 'name', 'value' => $crm_data['Name'] ) );
                if( ! empty( $response->records ) ) {
                    $Id = $response->records[0]->Id;
                    $update = hnsfUpdate('Contact', $update_crm, $Id);
                    $data['salesforce_id']= $update;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_contact'));
                }else{
                    $param = json_encode( $crm_data );
                    $account_id = hnsfInsert('Contact', $param);//hnsfInsert('Contact', $param);
                    $data['salesforce_id']= $account_id;
                    $data['status'] = 0;
                    $wpdb->update($tbl, $data, array('user_id' => $user_id));
                    wp_redirect(admin_url('admin.php?page=table_contact'));
                }
            }
        }
    }
    public function sync_queue_product(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_product';
        if(isset($_POST['btnSubmit']) && isset($_POST['product_id'])){
            $product_id = $_POST['product_id'];
            $data = array();
            $data['product_id']= $product_id;
            $product = wc_get_product($product_id);
            $data1['Name'] = $product->get_title();
            $data1['ProductCode'] = get_post_meta( $product_id, '_sku', true );

            $update_crm = array();
            foreach ($data1 as $product => $key){
                $update_crm[] = array( 'field' => $product, 'value' => $key);
            }
            $response = hnsfDuplicateItem( 'Product2', array( 'field' => 'ProductCode', 'value' => $data1['ProductCode'] ) );
            if($response->records[0]->Id){
                $Id = $response->records[0]->Id;
                $update = hnsfUpdate('Product2', $update_crm, $Id);
                $data['salesforce_id']= $update;
                $data['status'] = 0;
                $wpdb->update($tbl, $data, array('product_id' => $product_id));
                wp_redirect(admin_url('admin.php?page=table_product'));
            }else{
                $param = json_encode($data1);
                $Product2Id = hnsfInsert('Product2', $param);
                $data['salesforce_id']= $Product2Id;
                $data['status'] = 0;
                $wpdb->update($tbl, $data, array('product_id' => $product_id));
                wp_redirect(admin_url('admin.php?page=table_product'));
            }
        }
    }

    function create_admin_menu(){
        global $menu;
        include_once HNSALESFORCE_PATH .'admin/salesforce-admin.php';
        $admin = new SALESFORCE_ADMIN();
        add_menu_page(__('Salesforce', SALESFORCE_TEXT_DOMAIN), __('Salesforce', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce','salesforce', array($admin,'index' ));
        add_submenu_page ( 'salesforce', __ ( 'Table Lead', SALESFORCE_TEXT_DOMAIN ), __ ( 'Table Lead', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'table_lead', array($admin,'tabelLead' ));
        add_submenu_page ( 'salesforce', __ ( 'Table Account', SALESFORCE_TEXT_DOMAIN ), __ ( 'Table Account', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'table_account', array($admin,'tableAccount' ));
        add_submenu_page ( 'salesforce', __ ( 'Table Contact', SALESFORCE_TEXT_DOMAIN ), __ ( 'Table Contact', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'table_contact', array($admin,'tableContact' ));
        add_submenu_page ( 'salesforce', __ ( 'Table Product', SALESFORCE_TEXT_DOMAIN ), __ ('Table Product', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'table_product', array($admin,'tableProduct' ));
        add_submenu_page ( 'salesforce', __ ( 'Table Order', SALESFORCE_TEXT_DOMAIN ), __ ('Table Order', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'table_order', array($admin,'tableOrder' ));


        //add_submenu_page('admin.php?page=salesforce', __('Report sync data', SALESFORCE_TEXT_DOMAIN), __('Report sync data', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'salesforce', array($admin,'index' ));
    }
    public function add_settings_page() {
        $settings [] = include (HNSALESFORCE_PATH. 'admin/salesforce-settings.php');
        return apply_filters ( 'hnsalesforce_setting_classes', $settings );
    }
    /**
     *include necessary files for frontend function
     */
    public function include_for_frontend() {
        require_once HNSALESFORCE_PATH . 'classes/class-hn-salesforce-connector.php';
        require_once HNSALESFORCE_PATH . 'classes/salesforce-insert.php';
    }
    public function load_admin_scripts() {
        global $woocommerce;
        if (is_object($woocommerce))
            wp_enqueue_style ( 'woocommerce_admin_styles', $woocommerce->plugin_url () . '/assets/css/admin.css' );
        wp_enqueue_style('salesforcestyle', HNSALESFORCE_URL. '/assets/style.css');
    }
    public function install(){
        global $wpdb;
        $installed_version = get_option('magenest_salesforce_version');
        // install
        if (!$installed_version) {
            if (!function_exists('dbDelta')) {
                include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            }
            $prefix = $wpdb->prefix;
            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_order`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`order_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
            dbDelta($query);

            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_lead`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
            dbDelta($query);

            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_account`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
            dbDelta($query);

            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_contact`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
            dbDelta($query);

            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_product`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` varchar (255) NOT NULL,
				`salesforce_id` varchar (255) NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
            dbDelta($query);

            $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_report`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`woo_id` varchar (255)  NOT NULL,
				`sf_id` varchar (255)  NOT NULL,
				`type` varchar (255)  NOT NULL,
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            dbDelta($query);

            update_option('magenest_salesforce_version', self::VERSION);
        }
        if ( -1 === version_compare( $installed_version, self::VERSION ) )
            $this->upgrade( $installed_version );
    }
    
    public function upgrade($installed_version){
        global $wpdb;
        if (!function_exists('dbDelta')) {
            include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        $prefix = $wpdb->prefix;
        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_order`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`order_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
        dbDelta($query);

        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_lead`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
        dbDelta($query);

        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_account`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
        dbDelta($query);

        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_contact`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` varchar (255)  NOT NULL,
				`salesforce_id` varchar (255)  NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
        dbDelta($query);

        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_product`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` varchar (255) NOT NULL,
				`salesforce_id` varchar (255) NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
        dbDelta($query);

        $query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_report`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`woo_id` varchar (255)  NOT NULL,
				`sf_id` varchar (255)  NOT NULL,
				`type` varchar (255)  NOT NULL,
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        dbDelta($query);

        update_option('magenest_salesforce_version', self::VERSION);
    }
    
    function load_domain() {
        load_plugin_textdomain ( SALESFORCE_TEXT_DOMAIN, false, 'woocommerce-salesforce-crm-integration/languages/' );
    }

    /**
     * Get the singleton instance of our plugin
     *
     * @return class The Instance
     * @access public
     */
    public static function getInstance() {
        if (! self::$hnsalesforce_instance) {
            self::$hnsalesforce_instance = new HN_Salesforce_Integration();
        }
        return self::$hnsalesforce_instance;
    }
}
$hnsalesforce_loaded = HN_Salesforce_Integration::getInstance ();
