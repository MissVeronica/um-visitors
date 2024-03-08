<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_User_Options{

    public $current_time = 0;
    public $date_format   = 'Y/m/d H:i:s';
    public $date_local    = '';

    function __construct() {

        add_action( 'um_before_form',                 array( $this, 'user_viewing_profile_page' ), 900, 1 );
        add_filter( 'um_profile_tabs',                array( $this, 'add_tab_links_in_profile' ), 10, 1 );
        add_action( 'wp_logout',                      array( $this, 'add_last_logout_timestamp' ), 10, 1 );
        add_action( 'um_user_after_updating_profile', array( $this, 'add_last_update_timestamp' ), 10, 3 );

        $this->date_local   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $this->current_time = current_time( 'timestamp' );
    }

    public function directory_um_shortcode( $form_id ) {

        global $current_user;

        if ( ! empty( $form_id )) {

            set_transient( "vv_{$form_id}_{$current_user->ID}", um_profile_id(), DAY_IN_SECONDS );

            $shortcode = '[ultimatemember form_id="' . $form_id . '" /]';
            if ( version_compare( get_bloginfo('version'), '5.4', '<' ) ) {
                echo do_shortcode( $shortcode );

            } else {
                echo apply_shortcodes( $shortcode );
            }

        } else {
            echo '<h4>' . __( 'No Form defined', 'um-visitors' ) . '</h4>';
        }
    }

    public function get_form_max_users( $form_id, $counter ) {

        $max_users = get_post_meta( $form_id, '_um_max_users', true );

        if ( $max_users == '0' ) {
            $max_users = $counter;

        } else {
            if ( intval( $max_users ) > intval( $counter )) {
                $max_users = $counter;
            }
        }

        return $max_users;
    }

    public function profile_content_visitors_default( $args ) {

        $form_id = UM()->options()->get( 'vv_visitors_form_id' );

        $header = __( 'My latest visitors', 'um-visitors' );

        $vv = new Visitors_Shortcodes();
        if ( UM()->options()->get( 'vv_summary_weeks' ) == 1 ) {
            echo $vv->vv_show_daily( 'vv_visitors_counter', array(), $header );

        } else {
            echo $vv->vv_show_total_visitors_shortcode( array(), $header );
        }

        $this->directory_um_shortcode( $form_id );
    }

    public function profile_content_visits_default( $args ) {

        $form_id = UM()->options()->get( 'vv_visits_form_id' );

        $header = __( 'My latest visits', 'um-visitors' );

        $vv = new Visitors_Shortcodes();
        if ( UM()->options()->get( 'vv_summary_weeks' ) == 1 ) {
            echo $vv->vv_show_daily( 'vv_visits_counter', array(), $header );

        } else {
            echo $vv->vv_show_total_visits_shortcode( array(), $header );
        }

        $this->directory_um_shortcode( $form_id );
    }

    public function format_date( $time ) {

        return date_i18n( $this->date_format, $time );
    }

// 	Add UM filter to add links in the UM profile menu

    public function add_tab_links_in_profile( $tabs ) {

        global $current_user;

        $priority_user_role = UM()->roles()->get_priority_user_role( $current_user->ID );
        $roles_list = UM()->options()->get( 'vv_visitors_roles' );

        if ( is_array( $roles_list ) && in_array( $priority_user_role, $roles_list )) {

            if ( ! empty( UM()->options()->get( 'vv_visitors_form_id' ) )) {

                $tabs['vv_visitors'] = array( 'name'   => __( 'Visitors', 'um-visitors' ),
                                              'icon'   => 'um-faicon-users',
                                              'custom' => true,
                                            );

                add_action( 'um_profile_content_vv_visitors_default', array( $this, 'profile_content_visitors_default' ), 10, 1 );
            }

            if ( ! empty( UM()->options()->get( 'vv_visits_form_id' ) )) {

                $tabs['vv_visits'] = array(   'name'   => __( 'Visits', 'um-visitors' ),
                                              'icon'   => 'um-faicon-users',
                                              'custom' => true,
                                            );

                add_action( 'um_profile_content_vv_visits_default', array( $this, 'profile_content_visits_default' ), 10, 1 );
            }
        }

        return $tabs;
    }

//	UM Add last update logout timestamp to a user profile

    public function add_last_logout_timestamp( $user_id ) {

        update_user_meta( $user_id, 'vv_last_logout', $this->format_date( $this->current_time ) );
    }

//	UM Add last update timestamp to a user at profile update

    public function add_last_update_timestamp( $to_update, $user_id, $args ) {

        update_user_meta( $user_id, 'vv_last_update', $this->format_date( $this->current_time ) );
    }

//	UM access profile pages

    public function user_viewing_profile_page( $args ) {

        global $current_user;

        if ( ! empty( $current_user ) && isset( $current_user->ID )) {

            update_user_meta( $current_user->ID, 'vv_last_activity', $this->format_date( $this->current_time ) );
            //$vv = new Visitors_User();
            //$vv->reload_um_cache( $current_user->ID );
        }
    }
}

new Visitors_User_Options();
