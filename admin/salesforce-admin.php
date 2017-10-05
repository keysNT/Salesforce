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
        public function tableReport(){
            global $wpdb;
            $leadTbl = $wpdb->prefix.'magenest_queue_lead';
            $accountTbl = $wpdb->prefix.'magenest_queue_account';
            $contactTbl = $wpdb->prefix.'magenest_queue_contact';
            $productTbl = $wpdb->prefix.'magenest_queue_product';
            $orderTbl = $wpdb->prefix.'magenest_queue_order';
            
            $leadSql = 'SELECT * FROM '.$leadTbl.' WHERE status="0"';
            $accountSql = 'SELECT * FROM '.$accountTbl.' WHERE status="0"';
            $contactSql = 'SELECT * FROM '.$contactTbl.' WHERE status="0"';
            $productSql = 'SELECT * FROM '.$productTbl.' WHERE status="0"';
            $orderSql = 'SELECT * FROM '.$orderTbl.' WHERE status="0"';
            
            $leadResults = $wpdb->get_results($leadSql, ARRAY_A);
            $accountResults = $wpdb->get_results($accountSql, ARRAY_A);
            $contactResults = $wpdb->get_results($contactSql, ARRAY_A);
            $productResults = $wpdb->get_results($productSql, ARRAY_A);
            $orderResults = $wpdb->get_results($orderSql, ARRAY_A);

            $template_path = HNSALESFORCE_PATH.'templates/';
            $default_path = HNSALESFORCE_PATH.'templates/';
            $string = "<form method='post'>
                            <input type='hidden' name='action' value='call_table_report'/>
                            <select id='report_sync' name='report_sync'>
                                <option value='0'>----------</option>
                                <option value='lead'>Lead</option>
                                <option value='account'>Account</option>
                                <option value='contact'>Contact</option>
                                <option value='product'>Product</option>
                                <option value='order'>Order</option>
                            </select>
	                        <input type='submit' name='submitBtn' value='Report'/>
                        </form>";
            echo $string;
            if(isset($_POST['submitBtn'])){
                $report_table = isset($_POST['report_sync'])?$_POST['report_sync']:'lead';
                update_option('magenest_report',$report_table,'lead');
            }
            $report = get_option('magenest_report','lead');
            switch ($report){
                case 'lead':
                    wc_get_template( 'salesforce-report-lead.php', array('results' => $leadResults),$template_path,$default_path );
                    break;
                case 'account':
                    wc_get_template( 'salesforce-report-account.php', array('results' => $accountResults),$template_path,$default_path );
                    break;
                case 'contact':
                    wc_get_template( 'salesforce-report-contact.php', array('results' => $contactResults),$template_path,$default_path );
                    break;
                case 'product':
                    wc_get_template( 'salesforce-report-product.php', array('results' => $productResults),$template_path,$default_path );
                    break;
                case 'order':
                    wc_get_template( 'salesforce-report-order.php', array('results' => $orderResults),$template_path,$default_path );
                    break;
            }
        }
    }

?>