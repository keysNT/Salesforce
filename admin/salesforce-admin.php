<?php
    if ( ! defined( 'ABSPATH' ) ) exit;
    class SALESFORCE_ADMIN{
        public function __construct(){

        }
        public function index(){

        }
        public function tabelLead(){
            global $wpdb;
            $tbl = $wpdb->prefix.'magenest_queue_lead';
            $sql = 'SELECT * FROM '.$tbl.' WHERE status="1"';
            $results = $wpdb->get_results($sql, ARRAY_A);
            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            wc_get_template( 'salesforce-queue-lead.php', array('results' => $results),$template_path,$default_path );
        }
        public function tableProduct(){
            global $wpdb;
            $tbl = $wpdb->prefix.'magenest_queue_product';
            $sql = 'SELECT * FROM '.$tbl.' WHERE status="1"';
            $results = $wpdb->get_results($sql, ARRAY_A);
            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            wc_get_template( 'salesforce-queue-product.php', array('results' => $results),$template_path,$default_path );
        }
        public function tableAccount(){
            global $wpdb;
            $tbl = $wpdb->prefix.'magenest_queue_account';
            $sql = 'SELECT * FROM '.$tbl.' WHERE status="1"';
            $results = $wpdb->get_results($sql, ARRAY_A);
            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            wc_get_template( 'salesforce-queue-account.php', array('results' => $results),$template_path,$default_path );
        }
        public function tableContact(){
            global $wpdb;
            $tbl = $wpdb->prefix.'magenest_queue_contact';
            $sql = 'SELECT * FROM '.$tbl.' WHERE status="1"';
            $results = $wpdb->get_results($sql, ARRAY_A);
            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            wc_get_template( 'salesforce-queue-contact.php', array('results' => $results),$template_path,$default_path );
        }
        public function tableOrder(){
            global $wpdb;
            $tbl = $wpdb->prefix.'magenest_queue_order';
            $sql = 'SELECT * FROM '.$tbl.' WHERE status="1"';
            $results = $wpdb->get_results($sql, ARRAY_A);
            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            wc_get_template( 'salesforce-queue-order.php', array('results' => $results),$template_path,$default_path );
        }
    }

?>