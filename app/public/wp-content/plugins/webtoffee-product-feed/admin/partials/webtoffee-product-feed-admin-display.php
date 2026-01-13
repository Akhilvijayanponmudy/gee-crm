<?php
if (!defined('ABSPATH')) {
    exit;
}
$wf_admin_view_path=WT_PRODUCT_FEED_PLUGIN_PATH.'admin/views/';
$wf_img_path=WT_PRODUCT_FEED_PLUGIN_URL.'images/';
?>
<div class="wrap" id="<?php echo esc_attr( WEBTOFFEE_PRODUCT_FEED_ID );?>">
    <h2 class="wp-heading-inline">
    <?php esc_html_e('Settings', 'webtoffee-product-feed');?>
    </h2>
    <div class="nav-tab-wrapper wp-clearfix wt-pfd-tab-head">
        <?php
        $tab_head_arr=array(
            'wt-advanced'=>__('General', 'webtoffee-product-feed'),
            'wt-custom-fields' => __('Additional Fields', 'webtoffee-product-feed'),             
            'wt-pro-upgrade'=>__('Free vs Premium', 'webtoffee-product-feed'),           
            'wt-other-solutions' => __('Other Solutions', 'webtoffee-product-feed')
        );
        if(isset($_GET['debug']) && sanitize_text_field(wp_unslash($_GET['debug']))) //phpcs:ignore
        {
            $tab_head_arr['wt-debug']=__('Debug', 'webtoffee-product-feed');
        }
        Webtoffee_Product_Feed_Sync::generate_settings_tabhead($tab_head_arr);
        ?>
    </div>
    <div class="wt-pfd-tab-container">
        <?php
        //inside the settings form
        $setting_views_a=array(
            'wt-advanced'=>'admin-settings-advanced.php',
            //'wt-feed-channels'=>'feed-channels.php',
        );

        //outside the settings form
        $setting_views_b = array(
            'wt-custom-fields' => 'admin-settings-custom-fields.php',
            'wt-pro-upgrade' => 'freevspro.php',
            'wt-other-solutions' => 'admin-settings-other-solutions.php'
        );

        if(isset($_GET['debug']))//phpcs:ignore
        {
            $setting_views_b['wt-debug']='admin-settings-debug.php';
        }
        ?>
        <form method="post" class="wt_pf_settings_form_basic">
            <?php
            // Set nonce:
            if (function_exists('wp_nonce_field'))
            {
                wp_nonce_field(WEBTOFFEE_PRODUCT_FEED_ID);
            }
            foreach ($setting_views_a as $target_id=>$value) 
            {
                $settings_view=$wf_admin_view_path.$value;
                if(file_exists($settings_view))
                {
                    include $settings_view;
                }
            }
            ?>
            <?php 
            //settings form fields for module
            do_action('wt_pf_plugin_settings_form');?>           
        </form>
        <?php
        foreach ($setting_views_b as $target_id=>$value) 
        {
            $settings_view=$wf_admin_view_path.$value;
            if(file_exists($settings_view))
            {
                include $settings_view;
            }
        }
        ?>
        <?php do_action('wt_pf_plugin_out_settings_form');?> 
    </div>
    <?php //include $wf_admin_view_path."market.php";  ?>
</div>