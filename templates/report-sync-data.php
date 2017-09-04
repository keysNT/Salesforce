<?php
$data = getHnsfAccessToken();
//var_dump($data);
?>
<table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped pages" >
    <caption>
        <h1 class="caption">Report sync Salesforce</h1>
    </caption>
    <tr>
        <th><?php echo __('ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th><?php echo __('Woo ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th><?php echo __('Salesforce ID', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th><?php echo __('Type', 'SALESFORCE_TEXT_DOMAIN');?></th>
        <th><?php echo __('Create date', 'SALESFORCE_TEXT_DOMAIN'); ?></th>
    </tr>
    <?php foreach ($result_reports as $report): ?>
        <tr>
            <td><?= $report['id'] ?></td>
            <td>
                <?php if ($report['type'] == 'Lead'){ ?>
                    <a href="<?= admin_url().'user-edit.php?user_id='.$report['woo_id']; ?>">
                        <?= $report['woo_id'] ?>
                    </a>
                <?php }else{?>
                    <a href="<?= admin_url().'post.php?post='.$report['woo_id'].'&action=edit'; ?>">
                        <?= $report['woo_id'] ?>
                    </a>
                <?php } ?>
            </td>
            <td>
                <a href="<?= $data['instance_url'].'/one/one.app#/sObject/'.$report['sf_id']; ?>">
                    <?= $report['sf_id'] ?>
                </a>

            </td>
            <td><?php
                if ($report['type'] == 'Lead'){
                    echo $report['type'];
                }else{
                    echo $report['type'].' from order';
                }

                ?>
            </td>
            <td><?= $report['create_date'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>
