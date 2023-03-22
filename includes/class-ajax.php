<?php
class Hdq_Ajax_value{
    public function __construct() {
        add_action( 'wp_ajax_hdq_report_update', [ $this, 'hdq_report_update' ] );
        add_action( 'wp_ajax_nopriv_hdq_report_update', [ $this, 'hdq_report_update' ] );
        add_action( 'wp_ajax_hdq_download_csv', [ $this, 'hdq_download_csv' ] );
    }
    /**
     * Save Custom css option data
     *
     * @version 1.0.0
     */
    public function hdq_report_update() {
        if ( isset( $_POST ) ) {
            $security = sanitize_text_field( $_POST['nonce_validationd'] );
            if ( ! isset( $security ) || ! wp_verify_nonce( $security, 'hdq_nonce' ) ) {
                wp_die( -1, 403 );
            }
            if ( ! is_user_logged_in() ) {
                return false;
            }
            $post_title = sanitize_text_field( $_POST['current_quiz_name'] );
            $current_user_id = sanitize_text_field( $_POST['current_user_id'] );
            $answer_value = $_POST['answer_data'];
            $check_id_exits = $this->hdq_get_post_by_title($post_title);
            if ($check_id_exits) {
                $check_submit_data_before = get_post_meta($check_id_exits,"hdq_answer_$current_user_id",true);
                if ($check_submit_data_before) {
                    wp_send_json( [
                        'success'=>false
                    ] );
                } else {
                    update_post_meta($check_id_exits,"hdq_answer_$current_user_id",$answer_value );
                }
            } else {
                $insert_post = $this->insert_post_data($post_title);
                if( $insert_post ) {
                    update_post_meta($insert_post,"hdq_answer_$current_user_id", $answer_value );
                }
              
            }

           
        }
        die();
    }
        /**
     * Save Custom css option data
     *
     * @version 1.0.0
     */
    public function hdq_download_csv() {
        if ( isset( $_POST ) ) {
            $security = sanitize_text_field( $_POST['security'] );
            if ( ! isset( $security ) || ! wp_verify_nonce( $security, 'csv_download' ) ) {
                wp_die( -1, 403 );
            }
            if ( ! current_user_can( 'manage_options' ) ) {
                return false;
            }
            if ( ! is_user_logged_in() ) {
                return false;
            }
            $new_array = array();
           $post_id = sanitize_text_field($_POST['post_id']);
            $i = 0;
           $meta_data = get_post_meta($post_id);
           foreach($meta_data as $key => $value) {
                $parts = explode('_', $key );
                if ($parts[0] == 'hdq') {
                    preg_match_all('!\d+!', $key, $customer_ids);
                    $customer_id = $customer_ids[0][0];
                    $customer_data = get_userdata($customer_id);
                    $customer_name = $customer_data->display_name;
                    $new_array[$i][] = 'User Name->'.$customer_name;
                    $unsrilize = unserialize($value[0]);
                    $new_array[$i][] =  'The correct answer is '.$unsrilize[0].' of '.$unsrilize[1];
                    $i++;
                }
           }
           $file_name = get_the_title($post_id).'_report.csv';
            $this->hdq_d_csv_download($new_array,$file_name);
            echo esc_url(admin_url( $file_name ));
        }
        die();
    }


    /**
     * Export all subscribers
     *
     * @param array $array all subscriber.
     * @param String $filename default name subscribers csv.
     * @param String $delimiter default value :.
     */
    public function hdq_d_csv_download( $array, $filename = 'quiz_report.csv', $delimiter = ':' ) {
        $f = fopen( $filename, 'w+' );

        foreach ( $array as $line ) {
            fputcsv( $f, $line, $delimiter );
        }
        fseek( $f, 0 );
        if ( filesize( $filename ) > 0 ) {
             fread( $f, filesize( $filename ) );
        }
        fclose( $f );
    }

    public function insert_post_data($post_title) {
        $args = [
            'post_title'  => $post_title,
            'post_type'   => 'hdq_quiz_report',
            'post_status' => 'publish',

        ];
        $id = wp_insert_post( $args );
        if ( ! is_wp_error( $id ) ) {
            return $id;
        } else {
            return false;
        }
    }
    public function hdq_get_post_by_title($page_title, $output = OBJECT) {
        global $wpdb;
            $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='hdq_quiz_report'", $page_title ));
           return $post;
        return null;
    }
}
new Hdq_Ajax_value();