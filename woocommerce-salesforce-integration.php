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
            add_action ('admin_enqueue_scripts', array ($this,'load_admin_scripts' ), 99 );
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
                $data['salesforce_id']= order($order_id);
                $data['status'] = 0;
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
                $data['salesforce_id']= lead($user_id);
                $data['status'] = 0;
                $wpdb->update($tbl, $data, array('user_id' => $user_id));
                wp_redirect(admin_url('admin.php?page=table_lead'));
            }else{
                wp_redirect(admin_url('admin.php?page=table_lead'));
            }
        }
    }
    public function sync_queue_account(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_account';
        if(isset($_POST['btnSubmit']) && isset($_POST['user_id'])){
            $user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
            if ($user_id != 0){
                $data['salesforce_id']= account($user_id);
                $data['status'] = 0;
                $wpdb->update($tbl, $data, array('user_id' => $user_id));
                wp_redirect(admin_url('admin.php?page=table_account'));
            }else{
                wp_redirect(admin_url('admin.php?page=table_account'));
            }
        }
    }
    public function sync_queue_contact(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_contact';
        if(isset($_POST['btnSubmit']) && isset($_POST['user_id'])) {
            $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
            if ($user_id != 0) {
                $data['salesforce_id']= contact($user_id);
                $data['status'] = 0;
                $wpdb->update($tbl, $data, array('user_id' => $user_id));
                wp_redirect(admin_url('admin.php?page=table_contact'));
            }else{
                wp_redirect(admin_url('admin.php?page=table_contact'));
            }
        }
    }
    public function sync_queue_product(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_product';
        if(isset($_POST['btnSubmit']) && isset($_POST['product_id'])){
            $product_id = $_POST['product_id'];
            $data['salesforce_id']= product($product_id);
            $data['status'] = 0;
            $wpdb->update($tbl, $data, array('product_id' => $product_id));
            wp_redirect(admin_url('admin.php?page=table_product'));
        }
    }

    function create_admin_menu(){
        global $menu;
        include_once HNSALESFORCE_PATH .'admin/salesforce-admin.php';
        $admin = new SALESFORCE_ADMIN();
        add_menu_page(__('Salesforce', SALESFORCE_TEXT_DOMAIN), __('Salesforce', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce','salesforce', array($admin,'tableReport' ));
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
        require_once HNSALESFORCE_PATH . 'classes/controllers.php';
    }
    public function load_admin_scripts() {
        global $woocommerce;
        if (is_object($woocommerce))
            wp_enqueue_style ( 'woocommerce_admin_styles', $woocommerce->plugin_url () . '/assets/css/admin.css' );
        wp_enqueue_style('salesforcestyle', HNSALESFORCE_URL. '/assets/style.css');
        //wp_enqueue_script('salesforcescript', HNSALESFORCE_URL . '/assets/event.js');
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
