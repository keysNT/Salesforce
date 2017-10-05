<?php

function insert_lead($user_id){
    global $wpdb;
    $tbl = $wpdb->prefix.'magenest_queue_lead';
    $mode = get_option('hnsf_sync_salesforce');
    $data = array();
    $data['user_id'] = $user_id;
    if($mode == 'auto'){
        $data['salesforce_id']= lead($user_id);
        $data['status'] = 0;
    }else{
        $data['status'] = 1;
    }
    $wpdb->insert($tbl,$data);
}
add_action('user_register', 'insert_lead');

function insert_product($product_id){
    global $wpdb;
    $tbl = $wpdb->prefix.'magenest_queue_product';
    $mode = get_option('hnsf_sync_salesforce');
    $data = array();
    $data['product_id']= $product_id;
    $product = wc_get_product($product_id);
    $status = $product->get_status();
    if($status == 'publish'){
        if($mode == 'auto'){
            $data['salesforce_id']= product($product_id);
            $data['status'] = 0;
            $wpdb->insert($tbl,$data);
        }else{
            $data['status'] = 1;
            $wpdb->insert($tbl,$data);
        }
    }
}
add_action('save_post_product','insert_product');

function insert_contact($user_id, $load_address = ''){
    global $wpdb;
    $tbl = $wpdb->prefix.'magenest_queue_contact';
    $mode = get_option('hnsf_sync_salesforce');
    $data = array();
    if($mode == 'auto') {
        $data['salesforce_id'] = contact($user_id);
        $data['status'] = 0;
    }else{
        $data['status'] = 1;
    }
    $data['user_id'] = $user_id;
    $sql = $wpdb->prepare( "SELECT * FROM $tbl WHERE `user_id` = %d", $user_id);
    $result = $wpdb->get_row($sql, ARRAY_A);
    if(!empty($result)){
        $wpdb->update($tbl,$data,array('user_id' => $user_id));
    }else{
        $wpdb->insert($tbl,$data);
    }
}
add_action('woocommerce_customer_save_address', 'insert_contact',50,2);

function insert_account($user_id){
    global $wpdb;
    $tbl = $wpdb->prefix.'magenest_queue_account';
    $mode = get_option('hnsf_sync_salesforce');
    $data = array();
    if($mode == 'auto'){
        $data['salesforce_id']= account($user_id);
        $data['status'] = 0;
    }else{
        $data['status'] = 1;
    }
    $data['user_id'] = $user_id;
    $wpdb->insert($tbl,$data);
}
add_action('woocommerce_customer_save_address', 'insert_account',10,2);

function insert_order($order_id){
    global $wpdb;
    $tbl = $wpdb->prefix.'magenest_queue_order';
    $mode = get_option('hnsf_sync_salesforce');
    $data = array();
    if($mode == 'auto'){
        $data['salesforce_id']= order($order_id);
        $data['status'] = 0;
    }else{
        $data['status'] = 1;
    }
    $data['order_id'] = $order_id;
    $wpdb->insert($tbl,$data);
}
add_action('woocommerce_checkout_order_processed', 'insert_order',10,2);

/**
 * Check for duplicate items
 *
 * @param string $module
 * @param array $param
 *
 * @return array
 */
function hnsfDuplicateItem( $module, $param ) {
    $data = getHnsfAccessToken();
    $query = "SELECT Id FROM " . $module . " WHERE " . $param['field'] . " = '" . $param['value'] . "'";
    $url = $data['instance_url'] . "/services/data/v28.0/query?q=" . urlencode( $query );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $data['access_token']));

    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode( $json_response );

    curl_close($curl);
    if ( $status != 200 ) {
        updateHnsfAccessToken();
        hnsfDuplicateItem( $module, $param );
    }
    return $response;
}

?>