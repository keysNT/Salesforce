<?php

    function insert_lead($user_id){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_lead';
        $mode = get_option('hnsf_sync_salesforce');
        $data = array();
        $data['user_id'] = $user_id;
        if($mode == 'auto'){
            $user_data = get_userdata ($user_id);
            $lead_data = array ();
            $lead_data['Company'] = 'NA';
            $lead_data['LastName'] = $user_data->data->display_name;
            $lead_data['Email'] = $user_data->data->user_email;
            $param = json_encode( $lead_data );
            $lead_id = hnsfInsert('Lead', $param);
            $data['salesforce_id']= $lead_id;
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
                $data1['Name'] = $product->get_title();
                $data1['ProductCode'] = get_post_meta( $product_id, '_sku', true );
                $param = json_encode($data1);
                $Product2Id = hnsfInsert('Product2', $param);
                $data['salesforce_id']= $Product2Id;
                $data['status'] = 0;
                $wpdb->insert($tbl,$data);
                return $Product2Id;
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
            $crm_data = array();
            /** @var $order WC_Order */
            if (!empty($load_address)) {
                if($load_address == 'billing'){
                    $billing_street = get_user_meta($user_id,'billing_address_1','NA');
                    $billing_city = get_user_meta($user_id,'billing_city','NA');
                    $billing_state = get_user_meta($user_id,'billing_state','NA');
                    $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
                    $billing_country = get_user_meta($user_id, 'shipping_country','NA');
                    $billing_email = get_user_meta($user_id,'billing_email','NA');
                    $billing_phone = get_user_meta($user_id,'billing_phone','NA');
                }
                if($load_address == 'shipping'){
                    $shipping_street = get_user_meta($user_id,'shipping_address_1','NA');
                    $shipping_city = get_user_meta($user_id,'shipping_city','NA');
                    $shipping_state = get_user_meta($user_id,'shipping_state','NA');
                    $shipping_postcode  = get_user_meta($user_id,'shipping_postcode','NA');
                    $shipping_country = get_user_meta($user_id,'shipping_country','NA');
                }
            }else{
                $order = wc_get_order($user_id);
                $user = $order->get_user();
                $user_id = $order->get_user_id();

                $billing_street = $order->billing_address_1 . ' ' .$order ->  billing_address_1;
                $billing_city = $order->billing_city;
                $billing_state  = $order->billing_state;
                $billing_postcode   =  $order->billing_postcode;
                $billing_country    = $order->billing_country ;
                $billing_email 		= $order->billing_email;
                $billing_phone 		= $order->billing_phone;

                /** Shipping address */
                $shipping_street = $order->shipping_address_1 ;
                $shipping_city = $order->shipping_city;
                $shipping_state = $order->shipping_state;
                $shipping_postcode  = $order->shipping_postcode;
                $shipping_country = $order->shipping_country ;
            }
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
            // check duplicate item
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
            }else{
                $param = json_encode($crm_data);
                $contact_id = hnsfInsert('Contact', $param);
                $data['salesforce_id'] = $contact_id;
                $data['status'] = 0;
            }
        }else{
            if(empty($load_address)){
                $order = wc_get_order($user_id);
                $user_id = $order->get_user_id();
            }
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
    add_action('woocommerce_after_save_address_validation', 'insert_contact',50,2);

    function insert_account($user_id, $load_address = ''){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_account';
        $mode = get_option('hnsf_sync_salesforce');
        $data = array();
        if($mode == 'auto'){
            $crm_data = array();
            /** @var $order WC_Order */
            if(!empty($load_address)){
                if($load_address == 'billing'){
                    $billing_street = get_user_meta($user_id,'billing_address_1','NA');
                    $billing_city = get_user_meta($user_id,'billing_city','NA');
                    $billing_state = get_user_meta($user_id,'billing_state','NA');
                    $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
                    $billing_country = get_user_meta($user_id, 'shipping_country','NA');
                    $billing_email = get_user_meta($user_id,'billing_email','NA');
                    $billing_phone = get_user_meta($user_id,'billing_phone','NA');
                }
                if($load_address == 'shipping'){
                    $shipping_street = get_user_meta($user_id,'shipping_address_1','NA');
                    $shipping_city = get_user_meta($user_id,'shipping_city','NA');
                    $shipping_state = get_user_meta($user_id,'shipping_state','NA');
                    $shipping_postcode  = get_user_meta($user_id,'shipping_postcode','NA');
                    $shipping_country = get_user_meta($user_id,'shipping_country','NA');
                }
            }else{
                $order = wc_get_order($user_id);
                $user = $order->get_user();
                $user_id = $order->get_user_id();

                $billing_street = $order->billing_address_1 . ' ' .$order ->  billing_address_1;
                $billing_city = $order->billing_city;
                $billing_state  = $order->billing_state;
                $billing_postcode   =  $order->billing_postcode;
                $billing_country    = $order->billing_country ;
                $billing_email 		= $order->billing_email;
                $billing_phone 		= $order->billing_phone;

                /** Shipping address */
                $shipping_street = $order->shipping_address_1 ;
                $shipping_city = $order->shipping_city;
                $shipping_state = $order->shipping_state;
                $shipping_postcode  = $order->shipping_postcode;
                $shipping_country = $order->shipping_country ;
            }

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

            // check duplicate item
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
            }else{
                $param = json_encode( $crm_data );
                $account_id = hnsfInsert('Account', $param);
                $data['salesforce_id']= $account_id;
                $data['status'] = 0;
            }
        }else{
            if(empty($load_address)){
                $order = wc_get_order($user_id);
                $user_id = $order->get_user_id();
            }
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
    add_action('woocommerce_after_save_address_validation', 'insert_account',10,2);

    function insert_order($order_id){
        global $wpdb;
        $tbl = $wpdb->prefix.'magenest_queue_order';
        $mode = get_option('hnsf_sync_salesforce');
        $data = array();
        if($mode == 'auto'){
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
            $records = $response->records;
            $records = $records[0];
            if(empty($records)){
                insert_account($order_id, $load_address = '');
                insert_contact($order_id, $load_address = '');
                insert_order($order_id);
            }else{
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