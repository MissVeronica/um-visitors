<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_Directory{

    public $current_time  = 0;
    public $date_format   = 'Y/m/d H:i:s';
    public $date_local    = '';
    public $vv_user_ids   = false;

    function __construct() {

        add_filter( 'um_prepare_user_query_args',              array( $this, 'um_prepare_user_query_args_directories' ), 10, 2 );
        add_filter( 'um_prepare_user_query_args',              array( $this, 'um_prepare_user_query_args_vv' ), 10, 2 );
        add_filter( 'um_ajax_get_members_data',                array( $this, 'ajax_get_members_data_vv' ), 50, 3 );

        add_action( 'um_members_after_user_name_tmpl',         array( $this, 'um_members_after_user_name_tmpl_vv' ), 10, 1 );
        add_action( 'um_members_list_after_user_name_tmpl',    array( $this, 'um_members_after_user_name_tmpl_vv' ), 10, 1 );
        add_filter( 'um_member_directory_pre_display_sorting', array( $this, 'um_member_directory_vv_sorting' ), 10, 2 );

        add_action( 'deleted_user',                            array( $this, 'wp_deleted_user_update_vv' ), 10, 1 );
        add_filter( 'um_whitelisted_metakeys',                 array( $this, 'um_whitelisted_metakeys_vv' ), 10, 2 );

        $this->date_local   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $this->current_time = current_time( 'timestamp' );
    }

    public function vv_get_user_meta( $vv_user_id, $meta_key ) {

        $vv_user_ids = get_user_meta( $vv_user_id, $meta_key );

        if ( ! empty ( $vv_user_ids ) && is_array( $vv_user_ids ) && isset( $vv_user_ids[0] )) {
           $vv_user_ids = $vv_user_ids[0];
        }

        return $vv_user_ids;
    }

    public function get_all_user_ids( $meta_key, $form_id, $keys ) {

        global $current_user;

        $all_user_ids = array( false );

        $vv_user_id = get_transient( "vv_{$form_id}_{$current_user->ID}" );
        if ( ! empty( $vv_user_id )) {

            $vv_user_ids = $this->vv_get_user_meta( $vv_user_id, $meta_key );
            if ( ! empty ( $vv_user_ids )) {

                if ( $keys ) {
                    $all_user_ids = array_keys( $vv_user_ids );
                }
            }
        }

        return $all_user_ids;
    }

    public function format_date( $time, $local = false ) {

        if ( $local ) {
            return date_i18n( $this->date_format, $time );

        } else {
            return date_i18n( 'Y/m/d', $time );
        }
    }

    public function get_past_visit_time( $user_id ) {

        if ( UM()->options()->get( 'vv_number_of_days_ago' ) == 1 ) {
            return $this->number_of_days_ago( $this->vv_user_ids[$user_id] );

        } else {
            return $this->format_date( $this->vv_user_ids[$user_id] );
        }
    }

    public function ajax_get_members_data_vv( $data_array, $user_id, $directory_data ) {

        global $current_user;

        $vv_user_id = get_transient( "vv_{$directory_data['form_id']}_{$current_user->ID}" );
        if ( ! empty( $vv_user_id )) {

            switch( $directory_data['form_id'] ) {

                case UM()->options()->get( 'vv_visits_form_id' ):

                    $this->vv_user_ids = $this->vv_get_user_meta( $vv_user_id, 'vv_visits' );
                    if ( isset( $this->vv_user_ids[$user_id] )) {
                        $data_array['vv_visits'] = $this->get_past_visit_time( $user_id );
                    }
                    break;

                case UM()->options()->get( 'vv_visitors_form_id' ):

                    $this->vv_user_ids = $this->vv_get_user_meta( $vv_user_id, 'vv_visitors' );
                    if ( isset( $this->vv_user_ids[$user_id] )) {
                        $data_array['vv_visitors'] = $this->get_past_visit_time( $user_id );
                    }
                    break;

                default: break;
            }
        }

        return $data_array;
	}

    public function um_members_after_user_name_tmpl_vv( $args ) {

        if ( isset( $args['vv_show_visits'] ) && $args['vv_show_visits'] == 1 ) {
            echo '<div class="vv_header">' . __( 'Visit', 'um-visitors' ) . ' {{{user.vv_visits}}}</div>';
            return;
        }

        if ( isset( $args['vv_show_visitors'] ) && $args['vv_show_visitors'] == 1 ) {
            echo '<div class="vv_vv_header">' . __( 'Visitor', 'um-visitors' ) . ' {{{user.vv_visitors}}}</div>';
            return;
        }
    }

    public function um_member_directory_vv_sorting( $sorting_options, $args ) {

        switch( $args['form_id'] ) {

            case UM()->options()->get( 'vv_visits_form_id' ):
                                    $sorting_options['vv_visit_times'] = __( 'Visit time', 'um-visitors' );
                                    break;

            case UM()->options()->get( 'vv_visitors_form_id' ):
                                    $sorting_options['vv_visitor_times'] = __( 'Visitor time', 'um-visitors' );
                                    break;
            default: break;
        }

        return $sorting_options;
    }
    public function um_whitelisted_metakeys_vv( $cf_metakeys, $form_data ) {

        $vv_forbiddens = array( 'vv_last_activity',
                                'vv_last_update',
                                'vv_last_logout',
                                'vv_visits_counter',
                                'vv_visitors_counter'
                            );

        foreach( $vv_forbiddens as $vv_forbidden ) {
            $key = array_search( $vv_forbidden, $cf_metakeys );
            if ( $key ) {
                unset( $cf_metakeys[$key] );
            }
        }

        return $cf_metakeys;
    }

    public function um_prepare_user_query_args_directories( $query_args, $directory_data ) {

        switch( $directory_data['form_id'] ) {

            case UM()->options()->get( 'vv_visits_form_id' ):
                 $query_args['include'] = $this->get_all_user_ids( 'vv_visits', $directory_data['form_id'], true );
                 $query_args['include'] = $this->remove_users_hiding( $query_args['include'], false );
                 $query_args['include'] = $this->remove_deleted_users( $query_args['include'], false );
                 break;

            case UM()->options()->get( 'vv_visitors_form_id' ):
                 $query_args['include'] = $this->get_all_user_ids( 'vv_visitors', $directory_data['form_id'], true );
                 $query_args['include'] = $this->remove_users_hiding( $query_args['include'], false );
                 $query_args['include'] = $this->remove_deleted_users( $query_args['include'], false );
                 break;

            default: break;
        }

        return $query_args;
    }

    public function wp_deleted_user_update_vv( $user_id ) {

        $vv_deleted_users_ids = get_transient( 'vv_deleted_users_ids' );

        if ( $vv_deleted_users_ids ) {

            $vv_deleted_users_ids[$user_id] = true;
            set_transient( 'vv_deleted_users_ids', $vv_deleted_users_ids, WEEK_IN_SECONDS );
        }
    }

    public function remove_deleted_users( $vv_array, $key_search = true ) {

        $vv_deleted_users_ids = get_transient( 'vv_deleted_users_ids' );
        if ( ! $vv_deleted_users_ids ) {

            $args = array( 'fields'  => array( 'ID' ),
                           'orderby' => array( 'ID' => 'DESC')
                        );

            $active_users = get_users( $args );
            $last_user_id = $active_users[0]->ID;

            $vv_deleted_users_ids = array_fill( 1, $last_user_id, true );
            foreach( $active_users as $active_user ) {
                unset( $vv_deleted_users_ids[$active_user->ID] );
            }

            set_transient( 'vv_deleted_users_ids', $vv_deleted_users_ids, WEEK_IN_SECONDS );
        }

        foreach( $vv_deleted_users_ids as $user_id => $value ) {

            switch( $key_search ) {

                case 1:
                        if ( array_key_exists( $user_id, $vv_array )) {
                            unset( $vv_array[$user_id] );
                        }
                        break;

                case 0:
                        if ( in_array( $user_id, $vv_array )) {
                            $key = array_search( $user_id, $vv_array );
                            unset( $vv_array[$key] );
                        }
                        break;

                default:break;
            }
        }

        return $vv_array;
    }

    public function remove_users_hiding( $vv_array, $key_search = true ) {

        $args = array(  'fields'     => array( 'ID' ),
                        'meta_query' => array( 'relation'  => 'AND',
                                                array(  'key'     => 'hide_in_members',
                                                        'value'   => 'a:1:{i:0;s:3:"Yes";}',
                                                        'compare' => '=' )),
                    );

        $hidden_users = get_users( $args );

        foreach( $hidden_users as $hidden_user ) {

            switch( $key_search ) {
            
                case 1:
                        if ( array_key_exists( $hidden_user->ID, $vv_array )) {
                            unset( $vv_array[$hidden_user->ID] );
                        }
                        break;

                case 0:
                        if ( in_array( $hidden_user->ID, $vv_array )) {
                            $key = array_search( $hidden_user->ID, $vv_array );
                            unset( $vv_array[$key] );
                        }
                        break;

                default:break;
            }
        }

        return $vv_array;
    }

    public function prepare_sort_filter( $type, $vv_user_id ) {

        $this->vv_user_ids = $this->vv_get_user_meta( $vv_user_id, $type );
        $this->vv_user_ids = $this->remove_users_hiding( $this->vv_user_ids );
        $this->vv_user_ids = $this->remove_deleted_users( $this->vv_user_ids );

        add_filter( 'um_prepare_user_results_array', array( $this, 'um_prepare_user_results_array_vv_sorting' ), 10, 2 );
    }

    public function um_prepare_user_query_args_vv( $query_args, $directory_data ) {

        global $current_user;

        $form_id = $directory_data['form_id'];
        $visited_user_id = get_transient( "vv_{$form_id}_{$current_user->ID}" );

        switch( $query_args['orderby'] ) {

            case 'vv_visitor_times':
                 unset( $query_args['order'], $query_args['orderby'] );
                 $this->prepare_sort_filter( 'vv_visitors', $visited_user_id );
                 break;

            case 'vv_visit_times':
                 unset( $query_args['order'], $query_args['orderby'] );
                 $this->prepare_sort_filter( 'vv_visits', $visited_user_id );
                 break;

            default: break;
        }

        return $query_args;
    }

    public function um_prepare_user_results_array_vv_sorting( $user_ids, $query_args ) {

        if ( is_array( $this->vv_user_ids )) {

            if ( isset( $query_args['paged'] ) && isset( $query_args['number'] )) {

                $page_start = ( $query_args['paged'] - 1 ) * $query_args['number'];
                $page_subset = array_slice( $this->vv_user_ids, $page_start, $query_args['number'], true );

                $user_ids = array_keys( $page_subset );
            }
        }

        return $user_ids;
    }

    public function number_of_days_ago( $value ) {

        $time_diff = $this->current_time - $value;
        $value = intval( $time_diff/DAY_IN_SECONDS );

        if ( $value == 0 ) {
            $value = intval( $time_diff/HOUR_IN_SECONDS );
            $string = ( $value == 1 ) ? __( 'one hour ago', 'um-visitors' ) : __( '%d hours ago', 'um-visitors' );

            if ( $value == 0 ) {
                $value = intval( $time_diff/MINUTE_IN_SECONDS );
                $string = ( $value == 1 ) ? __( 'one minute ago', 'um-visitors' ) : __( '%d minutes ago', 'um-visitors' );

                if ( $value == 0 ) {
                    $string = __( 'less than one minute ago', 'um-visitors' );
                }
            }

        } else {

            $string = ( $value == 1 ) ? __( 'one day ago', 'um-visitors' ) : __( '%d days ago', 'um-visitors' );
        }

        return sprintf( $string, intval( $value ));
    }

}

new Visitors_Directory();
