<?php

?>
<style>
    *{margin: auto;}
    caption{
        font-size: 20px;
        margin-bottom: 30px;
        color: #0A246A;
    }
    table {
        width: 90%;
    }

    th {
        height: 30px;
    }
    th, td {
        padding: 15px;
        text-align: left;
    }
    th {
        background-color: #000000;
        color: white;
    }
    .cap{
        font-size: 18px;
    }
    input[type=submit]{
        height: 30px;
        padding: 5px;
        font-size: 14px;
        font-weight: bold;
        font-family: "Open Sans";
        text-transform: uppercase;
        color: #696666;
        border-radius: 2px;
        border: 0.15em solid #F9C23C;
        cursor: pointer;
        transition: all 0.3s ease 0s;
    }
    input[type="submit"]:hover {
        color: #fff;
        background-color: #EAA502;
        border-color: #EAA502;
        background-position: 0.75em bottom;
        -webkit-transition: all 0.3s ease;
        -ms-transition: all 0.3s ease;
        transition: all 0.3s ease;
    }
</style>
    <table cellpadding="0" cellspacing="0" style="text-align: left;">
        <caption>
            <?php echo __('Equeue sync Salesforce', 'SALESFORCE_TEXT_DOMAIN');?>
        </caption>
        <tr>
            <td colspan="6" class="cap">
                <?php echo __('Equeue order sync Salesforce', 'SALESFORCE_TEXT_DOMAIN');?>
            </td>

        </tr>
        <tr>
            <th><?php echo __('Order id', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Account name', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Email', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Product', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Order amount', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Order date', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Sync', 'SALESFORCE_TEXT_DOMAIN'); ?></th>
        </tr>
        <?php
            foreach ($results as $result):
                if ($result['status'] == 1){
                    continue;
                }
                $order = wc_get_order($result['order_id']);

                $user = $order->get_user();
                $account_name = $user->data->user_login;
                $email = $order->billing_email;
                $price = 0;
                foreach ($order->get_items () as $product){
                    $price += $product['subtotal'];
                }
               $create_date = $result['create_date'];
        ?>

        <tr>
            <td><?= $result['order_id']; ?></td>
            <td><?= $account_name ?></td>
            <td><?= $email ?></td>
            <td>
                <?php
                    foreach ($order->get_items() as $item){
                        $product_name = get_post($item['product_id']);
                        echo $product_name->post_title."<br/>";
                    }
                ?>
            </td>
            <td><?= $price ?></td>
            <td><?= $create_date ?></td>
            <td>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="syncSaleforce"/>
                    <input type="hidden" name="order_id" value="<?=$result['order_id']?>"/>
                    <input type="submit" name="btnSubmit" value="Sync"/>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>


    <table cellpadding="0" cellspacing="0" style="text-align: left; margin-top: 100px;">
        <tr>
            <td colspan="6" class="cap">
                <?php echo __('Equeue lead sync Salesforce', 'SALESFORCE_TEXT_DOMAIN');?>
            </td>

        </tr>
        <tr>
            <th><?php echo __('ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('User id', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Account name', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Email', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Date create', 'SALESFORCE_TEXT_DOMAIN');?></th>
            <th><?php echo __('Sync', 'SALESFORCE_TEXT_DOMAIN'); ?></th>
        </tr>
        <?php
            //var_dump($lead_results);
            foreach ($lead_results as $lead){
                $user_id = $lead['user_id'];
                $user = get_userdata($user_id);
                if($lead['status'] == 1){
                    continue;
                }
        ?>
        <tr>
            <td><?= $lead['id']; ?></td>
            <td><?= $lead['user_id']; ?></td>
            <td><?= $user->user_login; ?></td>
            <td><?= $user->user_email; ?></td>
            <td><?= $lead['create_date']; ?></td>
            <td>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="syncSaleforce"/>
                    <input type="hidden" name="user_id" value="<?=$lead['user_id']?>"/>
                    <input type="submit" name="btnSubmit" value="Sync"/>
                </form>
            </td>
        </tr>
        <?php
            }

        ?>
    </table>
