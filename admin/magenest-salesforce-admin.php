<?php


class Magenest_Salesforce_Admin{
    public function __construct(){

    }
    public function index(){
        global $wpdb;
        $prefix = $wpdb->prefix;
        $tbl = $prefix.'magenest_queue';
        $leadTbl = $prefix.'magenest_queue_lead';
        $sql = 'SELECT * FROM '.$tbl;
        $sql2 = 'SELECT * FROM '.$leadTbl;
        $results = $wpdb->get_results($sql, ARRAY_A);
        $lead_results = $wpdb->get_results($sql2, ARRAY_A);
        
        //$order = wc_get_order($order_id);
        $template_path = HNSALESFORCE_PATH.'templates/';
        $default_path = HNSALESFORCE_PATH.'templates/';
        wc_get_template( 'equeue-sync-salesforce.php', array('results' => $results, 'lead_results' => $lead_results),$template_path,$default_path );
    }
    public function queue_sync_order(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue';
        $sql = 'SELECT * FROM '.$tbl.' WHERE status = 0';
        $results = $wpdb->get_results($sql, ARRAY_A);
        $template_path = HNSALESFORCE_PATH.'templates/';
        $default_path = HNSALESFORCE_PATH.'templates/';
        wc_get_template( 'queue-sync-order.php', array('results' => $results),$template_path,$default_path );
    }
    public function queue_sync_lead(){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_lead';
        $sql = 'SELECT * FROM '.$tbl.' WHERE status = 0';
        $results = $wpdb->get_results($sql, ARRAY_A);
        $template_path = HNSALESFORCE_PATH.'templates/';
        $default_path = HNSALESFORCE_PATH.'templates/';
        wc_get_template( 'queue-sync-lead.php', array('results' => $results),$template_path,$default_path );
    }
    public function report_sync_data(){
        global $wpdb;
        $prefix = $wpdb->prefix;
        $reportTbl = $prefix.'magenest_salesforce_report';
        $sql = 'SELECT * FROM '.$reportTbl;
        $result_reports = $wpdb->get_results($sql, ARRAY_A);
        $template_path = HNSALESFORCE_PATH.'templates/';
        $default_path = HNSALESFORCE_PATH.'templates/';
        wc_get_template( 'report-sync-data.php', array('result_reports' => $result_reports),$template_path,$default_path );
        
    }
}