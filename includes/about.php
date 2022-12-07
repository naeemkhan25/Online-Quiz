<?php

if (!current_user_can('edit_others_pages')) {
    die("Your user account does not have access to these settings");
}


wp_enqueue_style(
    'hdq_admin_style',
    plugin_dir_url(__FILE__) . 'css/hdq_admin.css?v=' . HDQ_PLUGIN_VERSION
);

wp_enqueue_script(
    'hdq_admin_script',
    plugins_url('/js/hdq_admin.js?v=' . HDQ_PLUGIN_VERSION, __FILE__),
    array('jquery', 'jquery-ui-draggable'),
    HDQ_PLUGIN_VERSION,
    true
);
?>
<div id="main" style="max-width: 900px; background: #f3f3f3; border: 1px solid #ddd; margin-top: 2rem">
    <div id="header">
        <h1 id="heading_title" style="margin-top:0">
            Setting Options
        </h1>
    </div>

    <?php wp_nonce_field('hdq_about_options_nonce', 'hdq_about_options_nonce'); ?>

    <div style="display: grid; grid-template-columns: 1fr max-content; align-items: center;">
        <h2>
            Settings
        </h2>
        <div>
            <div role="button" title="save HDQ settings" class="hdq_button" id="hdq_save_settings">SAVE</div>
        </div>
    </div>


    <?php
    $fields = hdq_get_settings();
    if (!isset($quizID)) {
        $quizID = "";
    }
    ?>

    <div id="hdq_settings_page" class="content" style="display: block">
        <div id="content_tabs">
            <div id="tab_nav_wrapper">
                <div id="hdq_logo">
                    <span class="hdq_logo_tooltip"><img src="<?php echo plugins_url('/images/hd-logo.png', __FILE__); ?>" alt="Harmonic Design logo">
                        
                    </span>
                </div>
                <div id="tab_nav">
                    <?php hdq_print_settings_tabs(); ?>
                </div>
            </div>
            <div id="tab_content">
                <input type="hidden" class="hderp_input" id="quiz_id" style="display:none" data-required="true" data-type="integer" value="<?php echo $quizID; ?>" />
                <?php hdq_print_settings_tab_content($fields); ?>
            </div>
        </div>
    </div>
    <br />
</div>