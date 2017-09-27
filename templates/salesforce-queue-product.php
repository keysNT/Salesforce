<?php
$rows_per_page = 5;
$current = (isset($_REQUEST['paged'])&&intval($_REQUEST['paged']) ) ? intval($_REQUEST['paged']) : 1;
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
echo paginate_links($pagination_args);

$start = ($current - 1) * $rows_per_page;
$end = $start + $rows_per_page;
$end = (sizeof($results) < $end) ? sizeof($results) : $end;
?>
<table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped pages" >
    <caption>
        <h1 class="caption">Table queue sync Product to Salesforce</h1>
    </caption>
    <tr>
        <th class="syncQueue"><?php echo __('ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Product id', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Product name', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Sku', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Date create', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th class="syncQueue"><?php echo __('Sync', 'SALESFORCE_TEXT_DOMAIN'); ?></th>
    </tr>
    <?php
    for ($i=$start; $i<$end; $i++){
        $result = $results[$i];
        $product = wc_get_product($result['product_id']);
        $sku = get_post_meta($result['product_id'], '_sku', true );
        ?>
        <tr>
            <td><?= $result['id']; ?></td>
            <td><?= $result['product_id']; ?></td>
            <td><?= $product->get_title(); ?></td>
            <td><?= $sku; ?></td>
            <td><?= $result['create_date']; ?></td>
            <td>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="sync_product"/>
                    <input type="hidden" name="product_id" value="<?=$result['product_id']?>"/>
                    <input type="submit" name="btnSubmit" value="Sync" id="syncQueue"/>
                </form>
            </td>
        </tr>
        <?php
    }
    ?>
</table>