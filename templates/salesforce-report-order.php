<?php
$data = getHnsfAccessToken();
$rows_per_page = 5;
$current = (isset($_REQUEST['paged'])&&intval($_REQUEST['paged']) ) ? intval($_REQUEST['paged']) : 1;
$total = ceil(count($results)/$rows_per_page);
if($total < 2 ){
    $current = 1;
}
global $wp_rewrite;
$pagination_args = array(
    'base' => esc_url_raw(@add_query_arg('paged','%#%')),
    'format' => '&paged=%#%',
    'total' => ceil(count($results)/$rows_per_page),
    'current' => $current,
    'show_all' => false,
    'type' => 'plain',
);
if( $wp_rewrite->using_permalinks() )
    $pagination_args['base'] = user_trailingslashit( trailingslashit( remove_query_arg('s',get_pagenum_link(1) ) ) . '&paged=%#%', 'paged');

if( !empty($wp_query->query_vars['s']) )
    $pagination_args['add_args'] = array('s'=>get_query_var('s'));

$start = ($current - 1) * $rows_per_page;
$end = $start + $rows_per_page;
$end = (sizeof($results) < $end) ? sizeof($results) : $end;
?>
<table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped pages" id="order">
    <caption>
        <h1 class="caption">Table report sync Order to Salesforce</h1>
    </caption>
    <tr>
        <th class="syncQueue"><?php echo __('ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Order id', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('SalesForce id', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Date create', 'SALESFORCE_TEXT_DOMAIN');?></th>
    </tr>
    <?php
    for ($i=$start; $i<$end; $i++){
        $order = $results[$i];
        ?>
        <tr>
            <td>
                <?= $order['id']; ?>
            </td>
            <td>
                <a href="<?= admin_url().'post.php?post='.$order['order_id'].'&action=edit'; ?>">
                    <?= $order['order_id']; ?>
                </a>
            </td>
            <td>
                <a href="<?= $data['instance_url'].'/one/one.app#/sObject/'.$order['salesforce_id']; ?>">
                    <?= $order['salesforce_id'] ?>
                </a>
            <td>
                <?= $order['create_date']; ?>
            </td>
        </tr>
        <?php
    }
    ?>
    <tr>
        <td colspan="4">
            <?php echo paginate_links($pagination_args); ?>
        </td>
    </tr>
</table>
