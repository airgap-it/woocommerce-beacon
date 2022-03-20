<h2>Beacon configuration</h2>
<form action="" method="post">
<div class="wrap">
<table cellspacing="0" class="widefat options-table">
<thead>
<th>Active</th>
<th>Name</th>
<th>Symbol</th>
<th>Contract address</th>
<th>Token Id</th>
<th>Decimals</th>
<th>Rate</th>
</thead>
<?php
    foreach ($tokens as $key => $value) {
        $checked = '';
        if ($options[$value['identifier']]['active']) $checked = 'checked';
        $rate = 1;
        if($options[$value['identifier']]['rate']) $rate = $options[$value['identifier']]['rate'];
        ?>
        <tr>
        <td><input type="checkbox" name="<?php echo esc_attr($value['identifier']); ?>_active" <?php echo esc_attr($checked); ?>></td>
        <td><?php echo esc_attr($value['name']); ?></td>
        <td><?php echo esc_attr($value['symbol']); ?></td>
        <td><?php echo esc_attr($value['contract']); ?></td>
        <td><?php echo esc_attr($value['token_id']); ?></td>
        <td><?php echo esc_attr($value['decimals']); ?></td>
        <td><input type="number" name="<?php echo esc_attr($value['identifier']); ?>_rate" value="<?php echo esc_attr($rate); ?>" step="0.01"></td>
        </tr>
  <?php   } ?>
    </table>
    </div>
    <input type="hidden" name="page" value="'.$plugin_page.'" />
    <p><input type="submit" value="Save" class="button-primary autowidth"></p>
    <p>Go to <a href="admin.php?page=wc-settings&tab=checkout&section=beacon">plugin configuration</a></p>
    </form>