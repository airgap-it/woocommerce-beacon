<style>
    #beacon-status{
        border-radius: 15px;
        padding: 15px;
        margin: 15px 0;
        display: none;
    }
    .payment_method_beacon {
        background: none !important;
    }
</style>
<fieldset
    id="wc-<?php echo esc_attr($this->id); ?> -cc-form"
    class="wc-credit-card-form wc-payment-form"
    style="background: transparent;">
    <select id="beacon-select" name="beacon-select">
        <?php foreach (get_option('beacon_tokens') as $key => $value) {
            if($value["active"]){
        ?>
        <option value="<?php echo $value["identifier"]."-".$value["rate"]."-".$value["decimals"]."-".$value["contract"]."-".$value["token_id"] ?>"><?php echo $value["name"] ?> - <?php echo $this->get_order_total() * $value["rate"] ?> <?php echo $value["symbol"] ?></option>
        <?php }} ?>
    </select>
    <div id="beacon-status">
        <img
            id="beacon-img"
            src="<?php echo esc_url(plugins_url('/assets/svg/progress.svg', __FILE__ )); ?>"
            style="height: 64px; width: 64px;"
        />
        <div style="float: right; width: calc(100% - 80px);">
            <h4 id="beacon-heading"></h4>
            <p id="beacon-text"></p>
        </div>
    </div>
    <input id="beacon_transactionHash" name="beacon_transactionHash" type="hidden" autocomplete="off" />
    <button id="beacon-connect" class="button alt" onclick="startBeacon(event);"><?php echo esc_html($this->get_option("payment_button_text")); ?></button>
</fieldset>
