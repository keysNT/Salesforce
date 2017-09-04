<?php
/**
 * Plugin Name: WooCommerce Salesforce CRM Integration
 * Plugin URI: http://hungnamecommerce.com
 * Description: Synchronization
 * Author: Hungnam
 * Author URI:http://hungnamecommerce.com
 * Version: 1.1
 * Text Domain: woocommerce-salesforce-crm-integration
 * Domain Path: /languages/
 *
 * Copyright: (c) 2015 Hungnam. (info@hungnamecommerce.com)
 *
 *
 * @package   woocommerce-salesforce-crm-integration
 * @author    Hungnam
 * @category  Integration
 * @copyright Copyright (c) 2014, Hunganm, Inc.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (! defined ('SALESFORCE_TEXT_DOMAIN'))
	define ( 'SALESFORCE_TEXT_DOMAIN', 'hn_salesforce_integration' );

// Plugin Folder Path
if (! defined ('HNSALESFORCE_PATH'))
	define ('HNSALESFORCE_PATH', plugin_dir_path ( __FILE__ ) );

// Plugin Folder URL
if (! defined ('HNSALESFORCE_URL'))
	define ('HNSALESFORCE_URL', plugins_url ( 'woocommerce-salesforce-crm-integration', 'woocommerce-salesforce-crm-integration.php' ) );

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
	
	public function __construct() {
		global $wpdb;
		register_activation_hook(HNSALESFORCE_FILE, array($this, 'install'));
		add_action ( 'init', array ($this,'install' ), 1 );
		add_action ( 'init', array ($this,'load_domain' ), 1 );
		add_filter ( 'woocommerce_get_settings_pages', array ($this,'add_settings_page' ), 10, 1 );
		$this->include_for_frontend();
		if(is_admin()){
			add_action('admin_menu', array($this, 'create_admin_menu'), 5);
			add_action('admin_init', array($this, 'sync_admin_init'), 5);
			add_action ( 'admin_enqueue_scripts', array ($this,'load_admin_scripts' ), 99 );
		}

	}
	public function load_admin_scripts() {
		global $woocommerce;

		if (is_object($woocommerce))
			wp_enqueue_style ( 'woocommerce_admin_styles', $woocommerce->plugin_url () . '/assets/css/admin.css' );
		wp_enqueue_style('salesforcestyle', HNSALESFORCE_URL. '/assets/style.css');
	}
	public function sync_admin_init(){
		//add_action('admin_post_syncSaleforce', array($this, 'sync_equeue_salesforce'));
		add_action('admin_post_sync_order', array($this,'sync_queue_order'));
		add_action('admin_post_sync_lead', array($this,'sync_queue_lead'));
	}
	public function sync_queue_order(){
		global $wpdb;
		$prefix = $wpdb->prefix;
		if(isset($_POST['btnSubmit']) && isset($_POST['order_id'])){
			$order_id = isset($_POST['order_id'])?$_POST['order_id']:0;
			if ($order_id != 0){
				$arr_accounts = hnsfInsertAccount($order_id);
				$arr = hnsfInsertOrder($order_id);
			}
			$tbl = $prefix.'magenest_queue';
			$sf_reportTbl = $prefix.'magenest_salesforce_report';

			$data['status'] = 1;

			$wpdb->update($tbl, $data, array('order_id' => $order_id));
			$count = count($arr);
			for ($i=0; $i<$count; $i++){
				$sf_id = $arr[$i];
				$type = 'Product';
				$data_report = array();
				$data_report['sf_id'] = $sf_id;
				$data_report['woo_id'] = $order_id;
				$data_report['type'] = $type;
				$wpdb->insert($sf_reportTbl, $data_report);
			}

			//Account
			foreach ($arr_accounts as $account){
				$sf_id = $account['sf_id'];
				$type = $account['type'];
				$data_report = array();
				$data_report['sf_id'] = $sf_id;
				$data_report['woo_id'] = $order_id;
				$data_report['type'] = $type;
				$wpdb->insert($sf_reportTbl, $data_report);
			}
			wp_redirect(admin_url( 'admin.php?page=sync_order' ));
		}
	}
	public function sync_queue_lead(){
		global $wpdb;
		$prefix = $wpdb->prefix;
		if (isset($_POST['btnSubmit']) && isset($_POST['user_id'])){
			$user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
			if ($user_id != 0){
				hnsfInsertLead($user_id);
			}
			$leadTbl = $prefix.'magenest_queue_lead';
			$data['status'] = 1;
			$wpdb->update($leadTbl, $data, array('user_id' => $user_id));
			wp_redirect(admin_url('admin.php?page=sync_lead'));
		}
	}
//	public function sync_equeue_salesforce(){
//		global $wpdb;
//		$prefix = $wpdb->prefix;
//		if(isset($_POST['btnSubmit']) && isset($_POST['order_id'])){
//			$order_id = isset($_POST['order_id'])?$_POST['order_id']:0;
//			if ($order_id != 0){
//				$arr_accounts = hnsfInsertAccount($order_id);
//				$arr = hnsfInsertOrder($order_id);
//			}
//			$tbl = $prefix.'magenest_queue';
//			$sf_reportTbl = $prefix.'magenest_salesforce_report';
//
//			$data['status'] = 1;
//
//			$wpdb->update($tbl, $data, array('order_id' => $order_id));
//			$count = count($arr);
//			for ($i=0; $i<$count; $i++){
//				$sf_id = $arr[$i];
//				$type = 'Product';
//				$data_report = array();
//				$data_report['sf_id'] = $sf_id;
//				$data_report['woo_id'] = $order_id;
//				$data_report['type'] = $type;
//				$wpdb->insert($sf_reportTbl, $data_report);
//			}
//			
//			//Account
//			foreach ($arr_accounts as $account){
//				$sf_id = $account['sf_id'];
//				$type = $account['type'];
//				$data_report = array();
//				$data_report['sf_id'] = $sf_id;
//				$data_report['woo_id'] = $order_id;
//				$data_report['type'] = $type;
//				$wpdb->insert($sf_reportTbl, $data_report);
//			}
//			wp_redirect(admin_url( 'admin.php?page=salesforce' ));
//
//		}
//		if (isset($_POST['btnSubmit']) && isset($_POST['user_id'])){
//			$user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
//			if ($user_id != 0){
//				$arr = hnsfInsertLead($user_id);
//			}
//			$sf_reportTbl = $prefix.'magenest_salesforce_report';
//			$leadTbl = $prefix.'magenest_queue_lead';
//			$data['status'] = 1;
//			$wpdb->update($leadTbl, $data, array('user_id' => $user_id));
//			
//			$sf_id = $arr['sf_id'];
//			$type = $arr['type'];
//			$data_report = array();
//			$data_report['sf_id'] = $sf_id;
//			$data_report['woo_id'] = $user_id;
//			$data_report['type'] = $type;
//			$wpdb->insert($sf_reportTbl, $data_report);
//
//			wp_redirect(admin_url( 'admin.php?page=salesforce' ));
//		}
//	}
	
	/**
	 * Load the Text Domain for i18n
	 *
	 * @return void
	 * @access public
	 */
	///writting...
	function create_admin_menu(){
        global $menu;
        include_once HNSALESFORCE_PATH .'admin/magenest-salesforce-admin.php';
        $admin = new Magenest_Salesforce_Admin();
        add_menu_page(__('Salesforce', SALESFORCE_TEXT_DOMAIN), __('Salesforce', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce','salesforce', array($admin,'index' ));
		add_submenu_page ( 'salesforce', __ ( 'Sync Order', SALESFORCE_TEXT_DOMAIN ), __ ( 'Sync Order', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'sync_order', array($admin,'queue_sync_order' ));
		add_submenu_page ( 'salesforce', __ ( 'Sync Lead', SALESFORCE_TEXT_DOMAIN ), __ ( 'Sync Lead', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'sync_lead', array($admin,'queue_sync_lead' ));
		add_submenu_page ( 'salesforce', __ ( 'Report sync data', SALESFORCE_TEXT_DOMAIN ), __ ( 'Report sync data', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'report_sync_data', array($admin,'report_sync_data' ));

		//add_submenu_page('admin.php?page=salesforce', __('Report sync data', SALESFORCE_TEXT_DOMAIN), __('Report sync data', SALESFORCE_TEXT_DOMAIN), 'manage_woocommerce', 'salesforce', array($admin,'index' ));
    }

    //////////////////////////////////////////////////////////
	function load_domain() {
		load_plugin_textdomain ( SALESFORCE_TEXT_DOMAIN, false, 'woocommerce-salesforce-crm-integration/languages/' );
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
			$query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`order_id` int(11) NOT NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
			dbDelta($query);

			$query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_lead`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
			dbDelta($query);

			$query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_salesforce_report`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`woo_id` varchar (255)  NOT NULL,
				`sf_id` varchar (255)  NOT NULL,
				`type` varchar (255)  NOT NULL,
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			dbDelta($query);

			$query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_product`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` int(11) NOT NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
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
		$query = "CREATE TABLE IF NOT EXISTS `{$prefix}magenest_queue_product`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` int(11) NOT NULL,
				`status` int(11) NOT NULL DEFAULT '0',
				`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
		dbDelta($query);
		update_option('magenest_salesforce_version', self::VERSION);
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
		require_once HNSALESFORCE_PATH . 'classes/class-hn-salesforce-insert.php';
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
