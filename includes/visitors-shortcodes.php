<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_Shortcodes {

    public $vv_columns       = array();
    public $vv_last_activity = array();
    public $date_local       = '';
    public $date_format      = 'Y/m/d H:i:s';
    public $current_time     = 0;

    function __construct() {

        add_shortcode( 'vv_add_activity',    array( $this, 'vv_add_activity_shortcode' ));
        add_shortcode( 'vv_show_activity',   array( $this, 'vv_show_activity_shortcode' ));

        $this->vv_last_activity = array(
            'vv_last_activity'    => __( 'Last user activity %s ago',       'um-visitors' ),
            'vv_last_logout'      => __( 'Last user logout %s ago',         'um-visitors' ),
            'vv_last_update'      => __( 'Last user profile update %s ago', 'um-visitors' ),
            '_um_last_login'      => __( 'Last user login %s ago',          'um-visitors' ),
        );

        $this->date_local   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $this->current_time = current_time( 'timestamp' );
    }

    public function vv_add_activity_shortcode() {

        global $current_user;

        update_user_meta( $current_user->ID, 'vv_last_activity', date_i18n( $this->date_format, $this->current_time ) );
    }

    public function vv_show_activity_shortcode() {

        $message = '';

        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            $activities = array();

            foreach( $this->vv_last_activity as $meta_key => $value ) {
                if ( um_user( $meta_key )) {
                    $activities[$meta_key] = um_user( $meta_key );
                }
            }

            if ( count( $activities ) > 0 ) {
                arsort( $activities );

                $first_key = array_key_first( $activities );
                $message = sprintf( $this->vv_last_activity[$first_key], human_time_diff( strtotime( $activities[$first_key] ), $this->current_time ));
            }
        }

        return esc_attr( $message );
    }
}

new Visitors_Shortcodes();
