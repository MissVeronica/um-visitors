<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_User_Options{

    public $current_time = 0;
    public $date_format  = '';

    function __construct() {

        add_action( 'um_before_form',                 array( $this, 'user_viewing_profile_page' ), 900, 1 );
        add_filter( 'um_profile_tabs',                array( $this, 'add_tab_links_in_profile' ), 10, 1 );
        add_action( 'wp_logout',                      array( $this, 'add_last_logout_timestamp' ), 10, 1 );
        add_action( 'um_user_after_updating_profile', array( $this, 'add_last_update_timestamp' ), 10, 3 );

        $this->date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $this->current_time = current_time( 'timestamp' );
    }

    public function directory_um_shortcode( $form_id, $header, $daily ) {

        global $current_user;

        if ( ! empty( $daily )) {

            echo '<h4>' . $header . '</h4>';

            $week = '';
            if ( UM()->options()->get( 'vv_summary_weeks' ) == 1 ) {
                $week = __( 'Week ', 'um-visitors' );
            }

            foreach( $daily as $day => $number ) {
                echo '<div>' . $week . $day . ': ' . $number . '</div>';
            }

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

        } else {
            echo '<h4>' . __( 'No data for this User', 'um-visitors' ) . '</h4>';
        }
    }

    public function count_daily_visitors( $array ) {

        $counts = 0;

        if ( ! empty( $array )) {

            $counts = array();
            if ( UM()->options()->get( 'vv_summary_weeks' ) == 1 ) {

                $days = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
                $time_start = $days[get_option( 'start_of_week' )] . ' midnight';
                $time_length = 7 * DAY_IN_SECONDS;
                $index_format = 'W';
                $limit = intval( UM()->options()->get( 'vv_summary_limit' ));

            } else {
                $time_start = 'today midnight';
                $time_length = DAY_IN_SECONDS;
                $index_format = 'F d';
                $limit = intval( UM()->options()->get( 'vv_summary_limit' ));
            }

            $date = new DateTime( $time_start );
            $midnight = $date->getTimestamp() + $time_length;
            $index = date_i18n( $index_format, $midnight - $time_length );
            $counts[$index] = 0;

            foreach( $array as $id => $time ) {

                if ( $time < $midnight ) {
                    $counts[$index]++;

                } else {
                    $index = date_i18n( $index_format, $midnight );
                    $midnight = $midnight - $time_length;                
                    $counts[$index] = 0;
                    if ( $time < $midnight ) {
                        $counts[$index]++;
                    }
                }

                if ( count( $counts ) == $limit ) {
                    break;
                }
            }
        }

        return $counts;
    }

    public function get_form_max_users( $form_id, $counter ) {

        $max_users = get_post_meta( $form_id, '_um_max_users', true );

        if ( $max_users == '0' ) {
            $max_users = '';

        } else {
            if ( intval( $max_users ) > intval( $counter )) {
                $max_users = $counter;
            }
        }

        return $max_users;
    }

    public function profile_content_visitors_default( $args ) {

        $form_id = UM()->options()->get( 'vv_visitors_form_id' );

		$max_users = $this->get_form_max_users( $form_id, count( (array)um_user( 'vv_visitors' )) );
        $header = sprintf( __( 'My last %s of total %d visitors', 'um-visitors' ), $max_users, (array)count( um_user( 'vv_visitors' )) );

        $daily = $this->count_daily_visitors( um_user( 'vv_visitors' ) );

        $this->directory_um_shortcode( $form_id, $header, $daily );
    }

    public function profile_content_visits_default( $args ) {

        $form_id = UM()->options()->get( 'vv_visits_form_id' );
        $max_users = $this->get_form_max_users( $form_id, count( (array)um_user( 'vv_visits' )) );
        $header = sprintf( __( 'My last %s of total %d visits', 'um-visitors' ), $max_users, count( (array)um_user( 'vv_visits' )) );
        $daily = $this->count_daily_visitors( um_user( 'vv_visits' ) );

        $this->directory_um_shortcode( $form_id, $header, $daily );
    }

    public function format_date( $time ) {

        return date_i18n( 'Y/m/d H:i:s', $time );
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