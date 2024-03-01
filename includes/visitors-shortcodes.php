<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_Shortcodes {

    public $vv_columns       = array();
    public $vv_last_activity = array();
    public $date_local       = '';
    public $date_format      = 'Y/m/d H:i:s';
    public $current_time     = 0;

    function __construct() {

        add_shortcode( 'vv_add_activity',        array( $this, 'vv_add_activity_shortcode' ));
        add_shortcode( 'vv_show_activity',       array( $this, 'vv_show_activity_shortcode' ));
        add_shortcode( 'vv_show_total_visits',   array( $this, 'vv_show_total_visits_shortcode' ));
        add_shortcode( 'vv_show_total_visitors', array( $this, 'vv_show_total_visitors_shortcode' ));
        add_shortcode( 'vv_show_key_visits',     array( $this, 'vv_show_key_visits_shortcode' ));
        add_shortcode( 'vv_show_key_visitors',   array( $this, 'vv_show_key_visitors_shortcode' ));
        add_shortcode( 'vv_show_daily_visits',   array( $this, 'vv_show_daily_visits_shortcode' ));
        add_shortcode( 'vv_show_daily_visitors', array( $this, 'vv_show_daily_visitors_shortcode' ));

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

    public function vv_show_activity_shortcode( $attrs = array(), $content = '' ) {

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

    public function vv_show_total_visits_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_total_visitors( 'vv_visits_combo', $attrs, $content );
    }

    public function vv_show_total_visitors_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_total_visitors( 'vv_visitors_combo', $attrs, $content );
    }

    public function vv_show_total_visitors( $vv_type, $attrs, $content ) {

        ob_start();
        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {
            $vv_array = um_user( $vv_type );
            if ( is_array( $vv_array ) && ! empty( $vv_array )) {
                if( ! empty( $content )) {
                    echo '<h4>' . esc_attr( $content ) . '</h4>';
                }
                $text = array();
                if ( $vv_type == 'vv_visitors_combo' ) {
                    $text = array(  
                                    'today' => __( 'Visitors today %s',      'vv_visitors' ),
                                    'week'  => __( 'Visitors this week %s',  'vv_visitors' ),
                                    'month' => __( 'Visitors this month %s', 'vv_visitors' ),
                                    'total' => __( 'Visitors total %s',      'vv_visitors' ),
                                );
                }

                if ( $vv_type == 'vv_visits_combo' ) {
                    $text = array(  
                                    'today' => __( 'Visits today %s',      'vv_visitors' ),
                                    'week'  => __( 'Visits this week %s',  'vv_visitors' ),
                                    'month' => __( 'Visits this month %s', 'vv_visitors' ),
                                    'total' => __( 'Visits total %s',      'vv_visitors' ),
                                );
                }

                $vv_array = $this->validate_daily( $vv_array, $vv_type );

                foreach( $vv_array as $key => $value ) {
                    echo '<div>';
                    switch( $key ) {
                        case 'today':   echo sprintf( $text[$key], array_pop( $value )); break;
                        case 'week':    echo sprintf( $text[$key], array_pop( $value )); break;
                        case 'month':   echo sprintf( $text[$key], array_pop( $value )); break;
                        case 'total':   echo sprintf( $text[$key], $value ); break;
                        default: break;
                    }
                    echo '</div>';
                }

            } else {
                echo '<div>' . __( 'No combo data', 'um-visitors' ) . '</div>';
            }
        }
        return ob_get_clean();
    }

    public function vv_show_key_visits_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_key_visitors( 'vv_visits_combo', $attrs, $content );
    }
    
    public function vv_show_key_visitors_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_key_visitors( 'vv_visitors_combo', $attrs, $content );        
    }

    public function vv_show_key_visitors( $vv_type, $attrs, $content ) {

        ob_start();
        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            if ( isset( $attrs['key'] )) {

                $vv_array = um_user( $vv_type );
                if ( is_array( $vv_array ) && array_key_exists( $attrs['key'], $vv_array )) {

                    echo '<div>';
                    if ( $attrs['key'] != 'total' ) {
                        $vv_array = $this->validate_daily( $vv_array, $vv_type );
                        $value = array_pop( $vv_array[$attrs['key']] );

                    } else {
                        $value = $vv_array[$attrs['key']];
                    }

                    if ( ! empty( $content )) {
                        if ( ! strpos( $content, '%s')) {
                            $content .= ' %s';
                        }
                        echo esc_html( sprintf( $content, absint( $value )));

                    } else {
                        echo $value;
                    }

                    echo '</div>';

                } else {
                    echo '<div>' . sprintf( __( 'No %s data', 'um-visitors' ), $attrs['key'] ) . '</div>';
                }
            }
        }
        return ob_get_clean();
    }

    public function vv_show_daily_visits_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_daily( 'vv_visits_counter', $attrs, $content );
    }

    public function vv_show_daily_visitors_shortcode( $attrs = array(), $content = '' ) {

        return $this->vv_show_daily( 'vv_visitors_counter', $attrs, $content );
    }

    public function vv_show_daily( $vv_type, $attrs, $content = '' ) {

        ob_start();
        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {
            $vv_array = um_user( $vv_type );
            if ( is_array( $vv_array ) && ! empty( $vv_array )) {
                krsort( $vv_array );
                if ( isset( $attrs['limit'] )) {
                    $vv_array = array_slice( $vv_array, 0, absint( $attrs['limit'] ));
                }
                if( ! empty( $content )) {
                    echo '<h4>' . esc_attr( $content ) . '</h4>';
                }
                foreach( $vv_array as $key => $value ) {
                    echo '<div>';
                    echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ', strtotime( $key ))) . esc_attr( $value );
                    echo '</div>';
                }

            } else {
                echo '<div>' . __( 'No daily data', 'um-visitors' ) . '</div>';
            }
        }
        return ob_get_clean();
    }

    public function validate_daily( $vv_combo, $vv_type ) {

        $keys = array( 'today', 'week', 'month' );
        $update = false;

        foreach( $keys as $key ) {

            switch( $key ) {
                case 'today':   $today = date_i18n( 'Y/m/d', $this->current_time );
                                if ( ! isset( $vv_combo['today'][$today])) {
                                    $vv_combo['today'] = array( $today => 0 );
                                    $update = true;
                                }
                                break;

                case 'week':    $curr_week = date_i18n( 'W', $this->current_time );
                                if ( ! isset( $vv_combo['week'][$curr_week])) {
                                    $vv_combo['week'] = array( $curr_week => 0 );
                                    $update = true;
                                }
                                break;

                case 'month':   $curr_month = date_i18n( 'F', $this->current_time );
                                if ( ! isset( $vv_combo['month'][$curr_month])) {
                                    $vv_combo['month'] = array( $curr_month => 0 );
                                    $update = true;
                                }
                                break;
            }
        }

        if ( $update ) {
            update_user_meta( um_user( 'ID' ), $vv_type, $vv_combo );
            // cache
        }
        return $vv_combo;
    }
}

new Visitors_Shortcodes();
