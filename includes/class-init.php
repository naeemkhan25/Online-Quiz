<?php

class Hdq_Register_PostType{
    public function __construct() {
        add_action('init', [$this,'register_post_type']);
        add_filter( 'manage_hdq_quiz_report_posts_columns', [ $this, 'add_columns' ] );
        add_action( 'manage_hdq_quiz_report_posts_custom_column', [ $this, 'manage_columns' ], 10, 2 );
    }
    /**
     * Add columns for show subscribers list
     *
     * @param array $columns register columns for subscribers list.
     */
    public function add_columns( $columns ) {
        $newcolumns['cb']               = $columns['cb'];
        $newcolumns['quiz_name']         = __( 'Quiz Name', 'hdq' );
        $newcolumns['report']           = __( 'Quiz Report', 'hdq' );
        return apply_filters( 'hdq_quiz_report_add_new_columns', $newcolumns );
    }
    /**
     * Manage columns use for show subscribers data
     *
     * @param array $columns return all columns.
     * @param int $post_id return post ids.
     * @version 1.0.0
     */
    public function manage_columns( $columns, $post_id ) {
        $quiz_name = get_the_title($post_id);
        $meta = get_post_meta($post_id);
        
        switch ( $columns ) {
            case 'quiz_name':
                echo esc_html( $quiz_name );
                break;
            case 'report':
                echo sprintf('<div class="csv_download_section"><input type="hidden" id="download_post_id" value="%s"/><button class="hdq_csv_download" style="cursor:pointer;background-color:#547559;color:white">Download Report</button></div>',$post_id);
                break;
        }
    }
    /**
     * Register custom post type for stock notifier
     *
     * @return void
     *
     * @version 1.0.0
     */
    public function register_post_type() {
        $labels = [
            'name'               => _x( 'Quiz Report', ' Quiz Report', 'hdq' ),
            'singular_name'      => _x( ' Quiz Report', ' Quiz Report', 'hdq' ),
            'menu_name'          => _x( 'Quiz Report', 'Quiz Report', 'hdq' ),
            'name_admin_bar'     => _x( 'Stock Notifier', 'Name in Admin Bar', 'hdq' ),
            'add_new'            => _x( 'Add New Quiz', 'add new in menu', 'hdq' ),
            'add_new_item'       => __( 'Add New Quiz', 'hdq' ),   
            'edit_item'          => __( 'Edit Quiz', 'hdq' ),
            'view_item'          => __( 'View Quiz', 'hdq' ),
            'all_items'          => __( ' Notifications', 'hdq' ),
            'search_items'       => __( 'Search', 'hdq' ),
            'parent_item_colon'  => __( 'Parent:', 'hdq' ),
            'not_found'          => __( 'No Quiz Found', 'hdq' ),
            'not_found_in_trash' => __( 'No Quiz found in Trash', 'hdq' ),
        ];

        $args = [
            'labels'          => $labels,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'menu_icon'       => '<span class="dashicons dashicons-admin-page"></span>',
            'capability_type' => 'post',
            'capabilities'    => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap'    => true,
        ];

        do_action( 'hdq_register_post_type' );
        register_post_type( 'hdq_quiz_report', $args );

        flush_rewrite_rules();
    }
}
new Hdq_Register_PostType();