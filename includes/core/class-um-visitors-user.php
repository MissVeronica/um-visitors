<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_User{

    public $current_time        = 0;
    public $date_format         = 'Y/m/d H:i:s';
    public $date_index          = 'Y/m/d';
    public $date_local          = '';
    public $user_list_length    = 100;
    public $user_counter_length = 30;

    function __construct() {

        add_action( 'um_before_form', array( $this, 'user_viewing_profile_page' ), 999, 1 );

        $this->date_local   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $this->current_time = current_time( 'timestamp' );
    }

    public function reload_um_cache( $user_id ) {

        global $current_user;

        switch( $user_id ) {

            case um_profile_id():   UM()->user()->remove_cache( $user_id );
                                    um_fetch_user( $user_id );
                                    break;

            case $current_user->ID: UM()->user()->remove_cache( $user_id );
                                    um_fetch_user( um_profile_id() );
                                    break;

            default: break;
        }
    }

    public function required_meta_update( $vv_user_id, $vv_meta_value ) {

        $vv_meta_array = maybe_unserialize( $vv_meta_value );

        if ( ! empty( $vv_meta_array ) && is_array( $vv_meta_array )) {

            if ( key_exists( (int)$vv_user_id, $vv_meta_array )) {

                $vv_new_visit = absint( UM()->options()->get( 'vv_new_visit' ));

                if ( $vv_new_visit != 0 ) {
                    if (( $this->current_time - $vv_meta_array[$vv_user_id] ) < $vv_new_visit ) {
                        return false;
                    }
                }

                unset( $vv_meta_array[(int)$vv_user_id] );
            }

        } else {

            $vv_meta_array = array();
        }

        return $vv_meta_array;
    }

    public function update_user_meta_vv_array( $uid, $type, $array ) {

        if ( count( $array ) > $this->user_list_length ) {
            $array = array_slice( $array, 0, $this->user_list_length, true );
        }

        update_user_meta( $uid, $type, $array );
    }

    public function update_user_meta_vv_counter( $uid, $type ) {

        $vv_counter = um_user( $type );
        if ( empty( $vv_counter ) || ! is_array( $vv_counter )) {
            $vv_counter = array();
        }

        $today = date_i18n( $this->date_index, $this->current_time );

        if ( ! isset( $vv_counter[$today] )) {
            $vv_counter[$today] = 0;

            if ( count( $vv_counter ) > $this->user_counter_length ) {
                $vv_counter = array_slice( $vv_counter, 0, $this->user_counter_length, true );
            }
        }

        $vv_counter[$today]++;

        update_user_meta( $uid, $type, $vv_counter );
    }

    //	UM access profile pages

    public function user_viewing_profile_page( $args ) {

        global $current_user;

        if ( ! empty( $current_user->ID ) && ! empty( um_profile_id()) ) {
            if ( $current_user->ID != intval( um_profile_id()) ) {

                $this->update_profiles_vv( um_profile_id(), $current_user->ID );
            }
        }
    }

    public function update_profiles_vv( $visited_user_id, $visitor_user_id ) {

        //$this->reload_um_cache( $visited_user_id );
        $meta_data_visitors = $this->required_meta_update( $visitor_user_id, um_user( "vv_visitors" ));

        if ( $meta_data_visitors !== false && is_array( $meta_data_visitors )) {

            $meta_data_visitors = array( $visitor_user_id => $this->current_time ) + $meta_data_visitors;

            $this->update_user_meta_vv_array(   $visited_user_id, "vv_visitors", $meta_data_visitors );
            $this->update_user_meta_vv_counter( $visited_user_id, "vv_visitors_counter" );

            $this->reload_um_cache( $visited_user_id );
        }

        um_fetch_user( $visitor_user_id );
        $meta_data_visits = $this->required_meta_update( $visited_user_id, um_user( "vv_visits" ));

        if ( $meta_data_visits !== false && is_array( $meta_data_visits )) {

            $meta_data_visits = array( $visited_user_id => $this->current_time ) + $meta_data_visits;

            $this->update_user_meta_vv_array(   $visitor_user_id, "vv_visits", $meta_data_visits );
            $this->update_user_meta_vv_counter( $visitor_user_id, "vv_visits_counter" );
        }

        $this->reload_um_cache( $visitor_user_id );
    }

}

new Visitors_User();

