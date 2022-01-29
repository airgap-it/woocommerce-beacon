<style>
    #beacon-status{
        border-radius: 15px;
        padding: 15px;
        margin: 15px 0;
        display: none;
    }
    #place_order {
        display: none !important;
    }
    .payment_method_beacon {
        background: none !important;
    }
    label[for="payment_method_beacon"] {
        display: none !important;
    }
</style>
<fieldset
    id="wc-<?php echo esc_attr($this->id); ?> -cc-form"
    class="wc-credit-card-form wc-payment-form"
    style="background: transparent;">
    <div id="beacon-status">
        <img
            id="beacon-img"
            src="<?php echo esc_url(WP_PLUGIN_URL."/beacon-gateway/assets/svg/progress.svg"); ?>"
            style="height: 64px; width: 64px;"
        />
        <div style="float: right; width: calc(100% - 80px);">
            <h4 id="beacon-heading"></h4>
            <p id="beacon-text"></p>
        </div>
    </div>
    <input id="beacon_transactionHash" name="beacon_transactionHash" type="text" autocomplete="off" />
    <button id="beacon-connect" class="button alt" onclick="startBeacon(event);"><?php echo esc_html($this->get_option("payment_button_text")); ?></button>
</fieldset>
