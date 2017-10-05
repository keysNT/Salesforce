<?php
    function lead($user_id){
        $user_data = get_userdata ($user_id);
        $lead_data = array ();
        $lead_data['Company'] = 'NA';
        $lead_data['LastName'] = $user_data->data->display_name;
        $lead_data['Email'] = $user_data->data->user_email;

        $param = json_encode( $lead_data );
        $lead_id = hnsfInsert('Lead', $param);

        return $lead_id;
    }
    function contact($user_id){
        $crm_data = array();
        /** @var $order WC_Order */
        $billing_street = get_user_meta($user_id,'billing_address_1','NA');
        $billing_city = get_user_meta($user_id,'billing_city','NA');
        $billing_state = get_user_meta($user_id,'billing_state','NA');
        $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
        $billing_country = get_user_meta($user_id, 'shipping_country','NA');
        $billing_email = get_user_meta($user_id,'billing_email','NA');
        $billing_phone = get_user_meta($user_id,'billing_phone','NA');
        
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
            $contact_id = hnsfUpdate('Contact', $update_crm, $Id);
        }else{
            $param = json_encode($crm_data);
            $contact_id = hnsfInsert('Contact', $param);
        }
        return $contact_id;
    }
    function account($user_id){
        $crm_data = array();
        
        $billing_street = get_user_meta($user_id,'billing_address_1','NA');
        $billing_city = get_user_meta($user_id,'billing_city','NA');
        $billing_state = get_user_meta($user_id,'billing_state','NA');
        $billing_postcode = get_user_meta($user_id,'billing_postcode','NA');
        $billing_country = get_user_meta($user_id, 'shipping_country','NA');
        $billing_email = get_user_meta($user_id,'billing_email','NA');
        $billing_phone = get_user_meta($user_id,'billing_phone','NA');

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

        $param = json_encode( $crm_data );
        $account_id = hnsfInsert('Account', $param);

        return $account_id;
    }
    function product($product_id){
        $product = wc_get_product($product_id);
        $data1['Name'] = $product->get_title();
        $data1['ProductCode'] = get_post_meta( $product_id, '_sku', true );

        $param = json_encode($data1);
        $Product2Id = hnsfInsert('Product2', $param);

        return $Product2Id;
    }
    function order($order_id){
        global $wpdb;
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
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
            $contactTbl = $wpdb->prefix.'magenest_queue_contact';
            $sql = 'SELECT * FROM '.$contactTbl.' WHERE user_id = '.$user_id;
            $result = $wpdb->get_row($sql, ARRAY_A);
            if(!empty($result)){
                $data = array();
                if(empty($result['salesforce_id'])){
                    $contactId = contact($user_id);
                    $data['salesforce_id'] = $contactId;
                    $data['status'] = 0;
                    $wpdb->update($contactTbl, $data, array('user_id' => $user_id));
                }
            }else{
                $contactId = contact($user_id);
                $data['salesforce_id'] = $contactId;
                $data['status'] = 0;
                $wpdb->insert($contactTbl, $data);
            }
            $accountTbl = $wpdb->prefix.'magenest_queue_account';
            $sql = 'SELECT * FROM '.$accountTbl.' WHERE user_id = '.$user_id;
            $row = $wpdb->get_row($sql, ARRAY_A);
            if(!empty($row)){
                $data = array();
                if(empty($row['salesforce_id'])){
                    $accountId = account($user_id);
                    $data['salesforce_id'] = $accountId;
                    $data['status'] = 0;
                    $wpdb->update($accountTbl, $data, array('user_id' => $user_id));
                }
            }else{
                $accountId = account($user_id);
                $data['salesforce_id'] = $accountId;
                $data['status'] = 0;
                $wpdb->insert($accountTbl, $data);
            }
            order($order_id);
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

            foreach ($order->get_items() as $item){

                $productTbl = $wpdb->prefix.'magenest_queue_product';
                $product = $item->get_data();
                $product_id = $product['product_id'];
                $sql = 'SELECT * FROM '.$productTbl.' WHERE product_id = '.$product_id;
                $result = $wpdb->get_row($sql, ARRAY_A);
                if(!empty($result)){
                    $data = array();
                    if(empty($result['salesforce_id'])){
                        $Product2Id = product($product_id);
                        $data['salesforce_id'] = $Product2Id;
                        $data['status'] = 0;
                        $wpdb->update($productTbl, $data, array('product_id' => $product_id));
                    }else{
                        $Product2Id = $result['salesforce_id'];
                    }
                }else{
                    $Product2Id = product($product_id);
                    $data['salesforce_id'] = $Product2Id;
                    $data['status'] = 0;
                    $wpdb->insert($productTbl, $data);
                }
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
            return $orderId;
        }

    }
?>