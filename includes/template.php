<?php
// enqueue style and script
wp_enqueue_style(
    'hdq_admin_style',
    plugin_dir_url(__FILE__) . './css/hdq_style.css',
    array(),
    HDQ_PLUGIN_VERSION
);
wp_enqueue_script(
    'hdq_admin_script',
    plugins_url('./js/hdq_script.js?', __FILE__),
    array('jquery'),
    HDQ_PLUGIN_VERSION,
    true
);
wp_localize_script( 'hdq_admin_script', 'hdq_admin_script', [
    'ajax_url'       => admin_url( 'admin-ajax.php' ),
    'security'       => wp_create_nonce( 'hdq_nonce' ),
] );

$buildQuiz = true;

if (!defined('HDQ_REDIRECT')) {
    define('HDQ_REDIRECT', true);
}

if (!is_singular() && HDQ_REDIRECT) {
    // if we are on a category, search, or home blog page
    // replace quiz with direct link to post or page
    hdq_print_quiz_in_loop();
    $buildQuiz = false;
} else {
    if (function_exists("is_amp_endpoint")) {
        if (is_amp_endpoint()) {
            hdq_print_quiz_in_loop();
            $buildQuiz = false;
        }
    }

    // is this an admin page? Elementor won't enqueue scripts,
    // so do not print quiz
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    if (function_exists('is_plugin_active')) {
        // wrapped in another check since people might have diff wp-admin paths
        // depending on .htaccess mapping or firewalls before WP loads
        if (is_plugin_active('elementor/elementor.php')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class = "hdq_elementor_block" style = "padding: 2em; border: 1px dashed #999; background-color: rgba(255,255,255,0.1)"><p><strong>Online Quiz</strong>: This section is only visible because you are in Elementor\'s live edit mode, and will be replaced with the correct quiz on the public page/post.</p></div>';
                $buildQuiz = false;
            }
        }
    }
}


if ($buildQuiz === true) {
    $quiz_ID = intval($quiz); // quiz ID from shortcode

    // get quiz name
    $quiz_name = get_term($quiz_ID, "quiz");
    if ($quiz_name == null) {
        echo 'This quiz no longer exists';
        return;
    }
    $quiz_name = $quiz_name->name;

    $quiz_settings = get_hdq_quiz($quiz_ID);
    // get question order for query
    $question_order = "menu_order"; // default
    $ordering_value =  array_key_exists('randomize_questions',$quiz_settings) ? $quiz_settings["randomize_questions"]["value"][0]: 'no';
    $pool_question = array_key_exists('pool_of_questions',$quiz_settings) ? $quiz_settings["pool_of_questions"]["value"]: 0;
    if (
        $ordering_value === "yes" ||
        $pool_question > 0
    ) {
        $question_order = "rand";
    }

    $per_page = -1; // show all questions by default
    $paginate = false;
    $pagination = array_key_exists('wp_paginate',$quiz_settings) ? $quiz_settings["wp_paginate"]["value"]: 0;
    if ($pagination > 0) {
        if ($pool_question > 0) {
            return;
        } else {
            $paginate = true;
            $question_order = "menu_order";
            $per_page = $quiz_settings["wp_paginate"]["value"];
        }
    }

    if ( $pool_question > 0) {
        $per_page = $quiz_settings["pool_of_questions"]["value"];
    }

    $hdq_settings = hdq_get_settings();

    // if we should display ads
    $use_adcode = false;
    $hdq_adcode = hdq_decode(hdq_decode($hdq_settings["hd_qu_adcode"]["value"]));
    if ($hdq_adcode != "" && $hdq_adcode != null) {
        $hdq_adcode = stripcslashes(urldecode($hdq_adcode));
        $use_adcode = true;
    }

    $legacy_scroll = false;
    if (isset($hdq_settings["hd_qu_legacy_scroll"]["value"]) && $hdq_settings["hd_qu_legacy_scroll"]["value"][0] == "yes") {
        $legacy_scroll = true;
    }


    // Get the page or post featured image
    // (try to send to facebook for sharing results)
    $hdq_featured_image = "";
    if (has_post_thumbnail()) {
        $hdq_featured_image = wp_get_attachment_url(get_post_thumbnail_id(get_the_ID()), 'full');
    }

    $hdq_twitter_handle = $hdq_settings["hd_qu_tw"]["value"];

    $hide_questions = "";
    if (isset($quiz_settings["hide_questions"]["value"][0])) {
        $hide_questions = $quiz_settings["hide_questions"]["value"][0];
    }

    $finish = "Finish";
    if (!isset($hdq_settings["hd_qu_finish"]) || $hdq_settings["hd_qu_finish"]["value"] !== "") {
        $finish = $hdq_settings["hd_qu_finish"]["value"];
    }

    $next = "Next";
    if (!isset($hdq_settings["hd_qu_next"]) || $hdq_settings["hd_qu_next"]["value"] !== "") {
        $next = $hdq_settings["hd_qu_next"]["value"];
    }

    $results = "Results";
    if (!isset($hdq_settings["hd_qu_results"]) || $hdq_settings["hd_qu_results"]["value"] !== "") {
        $results = $hdq_settings["hd_qu_results"]["value"];
    }

    $translations = array(
        "finish" => $finish,
        "next" => $next,
        "results" => $results,
    );

    $jPaginate = false;
    // create object for localized script
    $hdq_local_vars = new \stdClass();
    $hdq_local_vars->hdq_quiz_id = $quiz_ID;
    $hdq_local_vars->hdq_timer = $quiz_settings["quiz_timer"]["value"];
    $hdq_local_vars->hdq_timer_question = $quiz_settings["quiz_timer_question"]["value"][0];
    $hdq_local_vars->hdq_show_results = $quiz_settings["show_results"]["value"][0];
    $hdq_local_vars->hdq_results_correct = $quiz_settings["show_results_correct"]["value"][0];
    $hdq_local_vars->hdq_show_extra_text = $quiz_settings["show_extra_text"]["value"][0];
    $hdq_local_vars->hdq_show_results_now = $quiz_settings["show_results_now"]["value"][0];
    $hdq_local_vars->hdq_stop_answer_reselect = $quiz_settings["stop_answer_reselect"]["value"][0];
    $hdq_local_vars->hdq_pass_percent = $quiz_settings["quiz_pass_percentage"]["value"];
    $hdq_local_vars->hdq_share_results = $quiz_settings["share_results"]["value"][0];
    $hdq_local_vars->hdq_hide_questions = $hide_questions;
    $hdq_local_vars->hdq_legacy_scroll = $legacy_scroll;
    $hdq_local_vars->hdq_quiz_permalink = get_the_permalink();
    $hdq_local_vars->hdq_twitter_handle = $hdq_twitter_handle;
    $hdq_local_vars->hdq_quiz_name = $quiz_name;
    $hdq_local_vars->hdq_ajax = admin_url('admin-ajax.php');
    $hdq_local_vars->hdq_featured_image = $hdq_featured_image;
    $hdq_local_vars->hdq_use_ads = $use_adcode;
    $hdq_local_vars->hdq_submit = array();
    $hdq_local_vars->hdq_init = array();
    $hdq_local_vars->hdq_translations = $translations;
    $hdq_local_vars->hdq_share_text = $hdq_settings["hd_qu_share_text"]["value"];
    do_action("hdq_submit", $hdq_local_vars); // add functions to quiz complete
    do_action("hdq_init", $hdq_local_vars); // add functions to quiz init
    $hdq_local_vars = json_encode($hdq_local_vars);
    wp_localize_script('hdq_admin_script', 'hdq_local_vars', array($hdq_local_vars));
    $current_user_id = get_current_user_id();
    if ( !$current_user_id) {
        return false;
    }
?>
    <div class="hdq_quiz_wrapper" id="hdq_<?php echo $quiz_ID; ?>">
        <div class="hdq_before">
            <?php do_action("hdq_before", $quiz_ID); ?>
            <h1 class="hdq_question" style="text-align:center"><?php echo $quiz_name;?></h1>
        </div>
        <div class="hdq_before">
            <h1 class="hdq_already_submit" style="text-align:center;color:red;display:none">You Already Submited</h1>
        </div>

        <?php
        hdq_print_quiz_start($quiz_settings["quiz_timer"]["value"], $use_adcode); ?>
        <div class="hdq_quiz" <?php if ($quiz_settings["quiz_timer"]["value"] > 3 && $use_adcode !== true) {
                                    echo 'style = "display:none;"';
                                } ?>>
            <input type="hidden" name="hdq_current_user_id" id="hdq_current_user_id" value="<?php echo $current_user_id; ?>" />
            <input type="hidden" name="hdq_current_term_name" id="hdq_current_term_name" value="<?php echo $quiz_name; ?>" />
            <?php
            if ($quiz_settings["results_position"]["value"] != "below") {
                hdq_get_results($quiz_settings);
            }

            // Query through questions
            wp_reset_postdata();
            wp_reset_query();
            global $post;
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

            // WP_Query arguments
            $args = array(
                'post_type' => array('post_type_questionna'),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'quiz',
                        'terms' => $quiz_ID,
                    ),
                ),
                'pagination' => $paginate, // true or false
                'posts_per_page' => $per_page, // also used for the pool of questions
                'paged' => $paged,
                'orderby' => $question_order, // defaults to menu_order
                'order' => 'ASC',
            );

            $query = new WP_Query($args);
            $i = 0; // question counter;

            // figure out the starting question number (for WP Pagination)
            $questionNumber = 0;
            if ($per_page >= 1 && $paged > 1) {
                $questionNumber = ($paged * $per_page) - $per_page + 1;
            }

            // The Loop
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $i++;
                    $question_ID = get_the_ID();
                    $question = get_hdq_question($question_ID);

                    if ($question["paginate"]["value"][0] === "yes") {
                        $jPaginate = true;
                        hdq_print_jPaginate($quiz_ID);
                    }

                    // used to add custom data attributes to questions					
                    $extra = apply_filters('hdq_extra_question_data', array(), $question, $quiz_ID);
                    $extra_data = "";
                    foreach ($extra as $k => $d) {
                        $extra_data = "data-" . $k . '="' . $d . '" ';
                    }
                    $extra_data = sanitize_text_field($extra_data);

                    echo '<div class = "hdq_question" ' . $extra_data . ' data-type = "' . $question["question_type"]["value"] . '" id = "hdq_question_' . $question_ID . '" data-weight = "1">';

                    hdq_print_question_featured_image($question);

                    do_action("hdq_after_featured_image", $question);

                    // deal with randomized answer order here,
                    // so that you don't have to in your custom question type functions
                    $answer_values = $ordering_value =  array_key_exists('randomize_answers',$quiz_settings) ? $quiz_settings["randomize_answers"]["value"][0]: 'no';
                    $ans_cor = hdq_get_question_answers($question["answers"]["value"], $question["selected"]["value"], $answer_values);
                    $question["answers"]["value"] = $ans_cor;
                    if ($question["question_type"]["value"] === "multiple_choice_text") {
                        hdq_multiple_choice_text($question_ID, $i, $question, $quiz_settings);
                    } elseif ($question["question_type"]["value"] === "multiple_choice_image") {
                        hdq_multiple_choice_image($question_ID, $i, $question, $quiz_settings);
                    } elseif ($question["question_type"]["value"] === "text_based") {
                        hdq_text_based($question_ID, $i, $question, $quiz_settings);
                    } elseif ($question["question_type"]["value"] === "title") {
                        $i = $i - 1; // don't count this as a question
                        hdq_title($question_ID, $i, $question, $quiz_settings);
                    } elseif ($question["question_type"]["value"] === "select_all_apply_text") {
                        hdq_select_all_apply_text($question_ID, $i, $question, $quiz_settings);
                    } else {
                        // TODO: Allow custom question types to be hookable
                        echo "Question type not found";
                    }
                    hdq_print_question_extra_text($question);
                    echo '</div>';

                    if ($use_adcode) {
                        if ($i % 5 == 0 && $i != 0) {
                            echo '<div class = "hdq_adset_container">';
                            echo $hdq_adcode;
                            echo '</div>';
                        }
                    }
                }
            }

            wp_reset_postdata();

            if ($query->max_num_pages > 1 || $per_page != "-1") {
                if (isset($_GET['currentScore'])) {
                    echo '<input type = "hidden" id = "hdq_current_score" value = "' . intval($_GET['currentScore']) . '"/>';
                }
                if (isset($_GET['totalQuestions'])) {
                    echo '<input type = "hidden" id = "hdq_total_questions" value = "' . intval($_GET['totalQuestions']) . '"/>';
                }

                if ($quiz_settings["pool_of_questions"]["value"] == 0 || $quiz_settings["pool_of_questions"]["value"] == "") {
                    if ($query->max_num_pages != $paged) {
                        hdq_print_next($quiz_ID, $paged);
                    }

                    if ($query->max_num_pages == $paged) {
                        hdq_print_finish($quiz_ID, $jPaginate);
                    }
                } else {
                    hdq_print_finish($quiz_ID, $jPaginate);
                }
            } else {
                hdq_print_finish($quiz_ID, $jPaginate);
            }

            if ($quiz_settings["results_position"]["value"] == "below") {
                hdq_get_results($quiz_settings);
            } ?>
        </div>
        <div class="hdq_after">
            <?php do_action("hdq_after", $quiz_ID); ?>
        </div>
        <div class="hdq_loading_bar"></div>
    </div>
<?php
}
?>