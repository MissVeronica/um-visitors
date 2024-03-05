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
        add_shortcode( 'vv_dashboard',           array( $this, 'vv_dashboard_shortcode' ));

        $this->vv_last_activity = array(
                            'vv_last_activity' => __( 'Last user activity %s ago',       'um-visitors' ),
                            'vv_last_logout'   => __( 'Last user logout %s ago',         'um-visitors' ),
                            'vv_last_update'   => __( 'Last user profile update %s ago', 'um-visitors' ),
                            '_um_last_login'   => __( 'Last user login %s ago',          'um-visitors' ),
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

    public function vv_dashboard_shortcode( $attrs = array(), $content = '' ) {

        ob_start();

        if( ! empty( $content )) {
            echo '<h4 class="vv_header">' . esc_attr( $content ) . '</h4>';
        }
        $this->show_dashboard_metabox_combo( __( 'Profile Page Viewers',    'um-visitors' ), 'vv_visitors_combo', true );
        $this->show_dashboard_metabox_combo( __( 'Visits to Profile Pages', 'um-visitors' ), 'vv_visits_combo',   true );

        return ob_get_clean();
    }

    public function show_dashboard_metabox_combo( $header, $counter, $hline ) {

        global $wpdb;

        $vv_combos = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = '{$counter}'" );

        if ( ! empty( $vv_combos ) && count( $vv_combos ) > 0 ) {

            $keys = array( 'today', 'week', 'month', 'total' );
            $totals = array( 'today' => array( 'x' => 0 ),
                             'week'  => array( 'x' => 0 ),
                             'month' => array( 'x' => 0 ),
                             'total' => 0,
                            );
            $max_values = array( 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0 );
            $max_userid = array( 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0 );

            foreach( $vv_combos as $vv_combo ) {

                $meta_value = $this->validate_daily( maybe_unserialize( $vv_combo->meta_value ), $counter, $vv_combo->user_id );

                foreach( $keys as $key ) {

                    switch( $key ) {
                        case 'today':
                        case 'week':
                        case 'month':   $value = array_pop( $meta_value[$key] );
                                        if ( $value > $max_values[$key] ) {
                                            $max_values[$key] = $value;
                                            $max_userid[$key] = $vv_combo->user_id;
                                        }
                                        $totals[$key]['x'] += $value;
                                        break;

                        case 'total':   $totals[$key] += $meta_value[$key];
                                        if ( $meta_value[$key] > $max_values[$key] ) {
                                            $max_values[$key] = $meta_value[$key];
                                            $max_userid[$key] = $vv_combo->user_id;
                                        }
                                        break;
                        default:        break;
                    }
                }
            }

            echo '<div class="vv_total" style="font-weight: bold;">' . __( 'Totals', 'um-visitors' ) . '</div>';
            echo $this->vv_show_total_visitors( $counter, $totals, '', '', false );
            echo '<hr>';

            echo '<div class="vv_top_users" style="font-weight: bold;">' . __( 'Top Users', 'um-visitors' ) . '</div>';
            foreach( $keys as $key ) {

                if ( $max_userid[$key] == 0 ) {
                    echo '<div class="vv_none">' . ucfirst( $key ) . ' ' . __( 'None', 'um-visitors' ) . '</div>';

                } else {
                    $user = get_user_by( 'ID', $max_userid[$key] );
                    $user = '<a href="' . esc_url( um_user_profile_url( $max_userid[$key] )) . '">' . esc_attr( $user->user_login ) . '</a>';
                    echo '<div class="vv_top_user">' . ucfirst( $key ) . ' ' . $user . ' ' . $max_values[$key] . '</div>';
                }
            }
            echo '<hr>';
        }
    }

    public function vv_show_total_visits_shortcode( $attrs = array(), $content = '' ) {

        $vv_array = um_user( 'vv_visits_combo' );
        return $this->vv_show_total_visitors( 'vv_visits_combo', $vv_array, $attrs, $content );
    }

    public function vv_show_total_visitors_shortcode( $attrs = array(), $content = '' ) {

        $vv_array = um_user( 'vv_visitors_combo' );
        return $this->vv_show_total_visitors( 'vv_visitors_combo', $vv_array, $attrs, $content );
    }

    public function vv_show_total_visitors( $vv_type, $vv_array, $attrs, $content, $validate = true ) {

        ob_start();
        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            if ( is_array( $vv_array ) && ! empty( $vv_array )) {

                if( ! empty( $content )) {
                    echo '<h4 class="vv_header">' . esc_attr( $content ) . '</h4>';
                }

                switch( $vv_type ) {
                        case 'vv_visitors_combo':
                            $text = array(
                                            'today' => __( 'Visitors today %s',      'vv_visitors' ),
                                            'week'  => __( 'Visitors this week %s',  'vv_visitors' ),
                                            'month' => __( 'Visitors this month %s', 'vv_visitors' ),
                                            'total' => __( 'Visitors total %s',      'vv_visitors' ),
                                        );
                            break;

                        case 'vv_visits_combo':
                            $text = array(  
                                            'today' => __( 'Visits today %s',      'vv_visitors' ),
                                            'week'  => __( 'Visits this week %s',  'vv_visitors' ),
                                            'month' => __( 'Visits this month %s', 'vv_visitors' ),
                                            'total' => __( 'Visits total %s',      'vv_visitors' ),
                                        );
                            break;

                        default: 
                            $text = array(); 
                            break;
                }

                if ( $validate ) {
                    $vv_array = $this->validate_daily( $vv_array, $vv_type );
                }

                if ( ! empty( $text )) {
                    foreach( $vv_array as $key => $value ) {
                        echo '<div class="vv_combo">';
                        switch( $key ) {
                            case 'today':   echo sprintf( $text[$key], array_pop( $value )); break;
                            case 'week':    echo sprintf( $text[$key], array_pop( $value )); break;
                            case 'month':   echo sprintf( $text[$key], array_pop( $value )); break;
                            case 'total':   echo sprintf( $text[$key], $value ); break;
                            default: break;
                        }
                        echo '</div>';
                    }
                }

            } else {
                echo '<div class="vv_combo">' . __( 'No combo data', 'um-visitors' ) . '</div>';
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

                    echo '<div class="vv_vombo">';
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
                    echo '<div class="vv_none">' . sprintf( __( 'No %s data', 'um-visitors' ), $attrs['key'] ) . '</div>';
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
                    echo '<h4 class="vv_header">' . esc_attr( $content ) . '</h4>';
                }

                foreach( $vv_array as $key => $value ) {
                    echo '<div class="vv_daily">';
                    echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ', strtotime( $key ))) . esc_attr( $value );
                    echo '</div>';
                }

                $vv_array = um_user( str_replace( 'counter', 'combo', $vv_type ) );

                echo '<div class="vv_daily">';
                echo sprintf( __( 'Total %s', 'um-visitors' ), $vv_array['total'] );
                echo '</div>';

            } else {
                echo '<div class="vv_none">' . __( 'No daily data', 'um-visitors' ) . '</div>';
            }
        }

        return ob_get_clean();
    }

    public function validate_daily( $vv_combo, $vv_type, $user_id = false ) {

        $keys = array( 'today', 'week', 'month' );
        $update = false;
        if ( is_array( $vv_combo ) && ! empty( $vv_combo )) {
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

                if ( ! $user_id ) {
                    $user_id = um_user( 'ID' );
                }
                update_user_meta( $user_id, $vv_type, $vv_combo );
                UM()->user()->remove_cache( $user_id );
            }
        }

        return $vv_combo;
    }
}

new Visitors_Shortcodes();
