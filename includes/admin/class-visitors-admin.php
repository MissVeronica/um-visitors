<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Visitors_Admin {

    public $heading_text     = '';
    public $directory_forms  = array( 0 => '' );

    function __construct() {

        add_filter( 'um_settings_structure', array( $this, 'visitors_settings' ), 99, 1 );
        add_shortcode( 'vv_activity',        array( $this, 'vv_activity_shortcode' ));

        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            add_filter( 'um_admin_extend_directory_options_profile', array( $this, 'member_directory_options_profile_visits' ), 10, 1 );
            add_filter( 'um_admin_extend_directory_options_profile', array( $this, 'member_directory_options_profile_visitors' ), 10, 1 );
            add_filter( 'um_predefined_fields_hook',                 array( $this, 'custom_predefined_fields_hook_visitors' ), 10, 1 );
            add_action( 'load-toplevel_page_ultimatemember',         array( $this, 'load_toplevel_page_visits_visitors' ) );

            add_filter( 'um_members_directory_sort_fields',          array( $this, 'um_members_directory_sort_fields_vv' ), 10, 1 );
            add_filter( 'um_members_directory_filter_fields',        array( $this, 'um_members_directory_sort_fields_vv' ), 10, 1 );
        }

        $um_directory_forms = get_posts( array( 'numberposts' => -1,
                                                'post_type'   => 'um_directory',
                                                'post_status' => 'publish'
                                            ));

        foreach( $um_directory_forms as $um_form ) {
            $this->directory_forms[$um_form->ID] = $um_form->post_title;
        }
    }

    public function load_toplevel_page_visits_visitors() {

        add_meta_box(   'um-metaboxes-sidebox-vv',
                        __( 'User Visitors & Visits', 'um-visitors' ),
                        array( $this, 'toplevel_page_visits_visitors' ),
                        'toplevel_page_ultimatemember',
                        'side',
                        'core'
                    );
    }

    public function toplevel_page_visits_visitors() {

        $this->show_dashboard_metabox_content( __( 'Visitors - Profile Viewers', 'um-visitors' ),       'vv_visitors_counter', true );
        $this->show_dashboard_metabox_content( __( 'Visits to Profile pages', 'um-visitors' ),          'vv_visits_counter',   true );
        ?>
        <div style="font-weight: bold;"><?php echo __( 'Last 24 hours', 'um-visitors' ) ?> </div>
        <?php
        $this->show_dashboard_metabox_stats( __( 'Visitors - Profile Viewers %d', 'um-visitors' ), 'vv_visitors', 1, false );
        $this->show_dashboard_metabox_stats( __( 'Visits to Profile pages %d', 'um-visitors' ),    'vv_visits',   1, true );
        ?>
        <div style="font-weight: bold;"><?php echo __( 'Last week', 'um-visitors' ) ?> </div>
        <?php
        $this->show_dashboard_metabox_stats( __( 'Visitors - Profile Viewers %d', 'um-visitors' ), 'vv_visitors', 7, false );
        $this->show_dashboard_metabox_stats( __( 'Visits to Profile pages %d', 'um-visitors' ),    'vv_visits',   7, true );
        ?>
        <div style="font-weight: bold;"><?php echo __( 'Last 24 hours', 'um-visitors' ) ?> </div>
        <?php
        $this->show_dashboard_last_activity(   __( 'Logins %d', 'um-visitors' ),          '_um_last_login',   1, false );
        $this->show_dashboard_last_activity(   __( 'Logouts %d', 'um-visitors' ),         'vv_last_logout',   1, false );
        $this->show_dashboard_last_activity(   __( 'Active Users %d', 'um-visitors' ),    'vv_last_activity', 1, false );
        $this->show_dashboard_last_activity(   __( 'Profile Updates %d', 'um-visitors' ), 'vv_last_update',   1, true );
        ?>
        <div style="font-weight: bold;"><?php echo __( 'Last week', 'um-visitors' ) ?> </div>
        <?php
        $this->show_dashboard_last_activity(   __( 'Logins %d', 'um-visitors' ),          '_um_last_login',   7, false );
        $this->show_dashboard_last_activity(   __( 'Logouts %d', 'um-visitors' ),         'vv_last_logout',   7, false );
        $this->show_dashboard_last_activity(   __( 'Active Users %d', 'um-visitors' ),    'vv_last_activity', 7, false );
        $this->show_dashboard_last_activity(   __( 'Profile Updates %d', 'um-visitors' ), 'vv_last_update',   7, false );
    }

    public function show_dashboard_last_activity(  $header, $counter, $days, $hline ) {

        global $wpdb;

        $vv_counter = $wpdb->get_results( "SELECT COUNT(*) AS number FROM {$wpdb->usermeta} WHERE meta_key = '{$counter}' AND meta_value > NOW() - INTERVAL {$days} DAY" );

        if ( ! empty( $vv_counter ) && count( $vv_counter ) > 0 ) { ?>
            <div><?php echo sprintf( $header, intval( $vv_counter[0]->number )); ?> </div>
            <?php
            if ( $hline ) {
                echo '<hr>';
            }
        }
    }

    public function show_dashboard_metabox_stats( $header, $counter, $days, $hline ) {

        global $wpdb;

        $vv_counter = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = '{$counter}'" );

        if ( ! empty( $vv_counter ) && count( $vv_counter ) > 0 ) {

            $total = 0;
            $limit = time() - ( $days *  DAY_IN_SECONDS );

            foreach( $vv_counter as $counter ) {
                $meta_value = maybe_unserialize( $counter->meta_value );
                foreach( $meta_value as $time ) {
                    if ( $time > $limit ) {
                        $total++;
                    } else {
                        break;
                    }
                }
            }
            ?>
            <div><?php echo sprintf( $header, $total ); ?> </div>
            <?php
            if ( $hline ) {
                echo '<hr>';
            }
        }
    }

    public function show_dashboard_metabox_content( $header, $counter, $hline ) {

        global $wpdb;

        $vv_counter = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = '{$counter}'" );

        if ( ! empty( $vv_counter ) && count( $vv_counter ) > 0 ) {

            $max = 0;

            foreach( $vv_counter as $counter ) {

                if ( intval( $counter->meta_value ) > $max ) {
                    $max = intval( $counter->meta_value );
                    $user_id = $counter->user_id;
                }
            }

            $user = get_user_by( 'ID', $user_id );
            $user = '<a href="' . esc_url( um_user_profile_url( $user_id )) . '">' . $user->user_login . '</a>';

            ?>
            <div style="font-weight: bold;"><?php echo $header ?> </div>
            <div><?php echo sprintf( __( 'Number of Users %d',     'um-visitors' ), count( $vv_counter )); ?> </div>
            <div><?php echo sprintf( __( 'Max ID %s (%s) with %d', 'um-visitors' ), $user_id, $user, $max ); ?> </div>
            <?php
            if ( $hline ) {
                echo '<hr>';
            }
        }
    }

    public function um_members_directory_sort_fields_vv( $sort_fields ) {

        $sort_fields['vv_visitor_times'] = __( 'Visitor times', 'um-visitors' );
        $sort_fields['vv_visit_times']   = __( 'Visit times', 'um-visitors' );

        return $sort_fields;
    }

    public function vv_activity_shortcode() {

        global $current_user;

        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            update_user_meta( $current_user->ID, 'vv_last_activity', date_i18n( $this->date_format, $this->current_time ) );
        }
    }

    public function visitors_settings( $settings ) {

        if ( ! empty( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'extensions' ) {

            $this->heading_text = __( 'User Visitors Settings', 'um-visitors' );

            $all_fields = array();

            $all_fields[] = array(  'id'          => 'visitors_active',
                                    'type'        => 'checkbox',
                                    'default'     => 1,
                                    'label'       => __( 'Activate the extension', 'um-visitors' ),
                                );

            $all_fields[] = array(  'id'          => 'vv_visitors_roles',
                                    'type'        => 'select',
                                    'multi'       => true,
                                    'options'     => UM()->roles()->get_roles(),
                                    'label'       => __( 'User Roles', 'um-visitors' ),
                                    'description' => __( 'Select the User Roles for display of the Visits & Visitors forms in User Profiles.'),
                                    'size'        => 'medium',
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $all_fields[] = array(  'id'          => 'vv_new_visit',
                                    'type'        => 'select',
                                    'multi'       => false,
                                    'label'       => __( 'Time limit new entries', 'um-visitors' ),
                                    'description' => __( 'Visitor time limit for new visit entries to be saved.<br />
                                                          Select a number in minutes/hours ( 5 minutes to 24 hours ).<br />
                                                          During this time period duplicate visits are not saved as a new visit.<br />
                                                          Disable time limit, set time to 0', 'um-visitors' ),
                                    'options'     => array( '0'     => '0',
                                                            '300'   => '5 minutes',
                                                            '600'   => '10 minutes',
                                                            '1200'  => '20 minutes',
                                                            '1800'  => '30 minutes',
                                                            '2400'  => '40 minutes',
                                                            '3000'  => '50 minutes',
                                                            '3600'  => '1 hour',
                                                            '7200'  => '2 hours',
                                                            '10800' => '3 hours',
                                                            '14400' => '4 hours',
                                                            '18000' => '5 hours',
                                                            '21600' => '6 hours',
                                                            '43200' => '12 hours',
                                                            '86400' => '24 hours'
                                                        ),
                                    'size'        => 'small',
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $all_fields[] = array(  'id'          => 'vv_visitors_form_id',
                                    'type'        => 'select',
                                    'options'     => $this->directory_forms,
                                    'label'       => __( 'Visitors - Member Directory Form', 'um-visitors' ),
                                    'description' => __( 'Create a Member Directory Form for display of User Profile Visitors.', 'um-visitors' ),
                                    'size'        => 'medium',
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );
 
            if ( ! empty( UM()->options()->get( 'vv_visitors_form_id' ))) {
                unset( $this->directory_forms[UM()->options()->get( 'vv_visitors_form_id' )] );
            }

            $all_fields[] = array(  'id'          => 'vv_visits_form_id',
                                    'type'        => 'select',
                                    'options'     => $this->directory_forms,
                                    'label'       => __( 'Visits - Member Directory Form', 'um-visitors' ),
                                    'description' => __( 'Create a Member Directory Form for display of User Profile Visits.', 'um-visitors' ),
                                    'size'        => 'medium',
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $all_fields[] = array(  'id'          => 'vv_number_of_days_ago',
                                    'type'        => 'checkbox',
                                    'label'       => __( 'Display days ago in Members Directory', 'um-visitors' ),
                                    'description' => __( 'If not selected WP date format will be used.', 'um-visitors' ),
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $all_fields[] = array(  'id'          => 'vv_summary_weeks',
                                    'type'        => 'checkbox',
                                    'label'       => __( 'Counters per week', 'um-visitors' ),
                                    'description' => __( 'If not selected counters will use days.', 'um-visitors' ),
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $all_fields[] = array(  'id'          => 'vv_summary_limit',
                                    'type'        => 'text',
                                    'label'       => __( 'Number of counters', 'um-visitors' ),
                                    'description' => __( 'Enter the number of days/weeks for User counter displays.', 'um-visitors' ),
                                    'size'        => 'small',
                                    'conditional' => array( "visitors_active", '=', 1 )
                                );

            $key = ! empty( $settings['extensions']['sections'] ) ? 'visitors' : '';
            $plugin_data = get_plugin_data( plugin_visitors_file );

            $settings['extensions']['sections'][$key] = array(  'title'  => __( 'User Visitors & Visits', 'um-visitors' ),
                                                                'description' => sprintf( __( 'Plugin version %s - tested with UM 2.8.3'), $plugin_data['Version'] ),
                                                                'fields' => $all_fields );
        }

        return $settings;
    }

    public function member_directory_options_profile_visitors( $fields ) {

		$fields = array_merge(
                                array_slice( $fields, 0, 3 ),
                                array(
                                    array(
                                        'id'    => '_um_vv_show_visitors',
                                        'type'  => 'checkbox',
                                        'label' => __( 'Show visitors time', 'um-visitors' ),
                                        'value' => UM()->query()->get_meta_value( '_um_vv_show_visitors', null, 'na' ),
                                    ),
                                ),
                                array_slice( $fields, 3, count( $fields ) - 1 )
                            );

		return $fields;
	}

    public function member_directory_options_profile_visits( $fields ) {

		$fields = array_merge(
                                array_slice( $fields, 0, 3 ),
                                array(
                                    array(
                                        'id'    => '_um_vv_show_visits',
                                        'type'  => 'checkbox',
                                        'label' => __( 'Show visits time', 'um-visitors' ),
                                        'value' => UM()->query()->get_meta_value( '_um_vv_show_visits', null, 'na' ),
                                    ),
                                ),
                                array_slice( $fields, 3, count( $fields ) - 1 )
                            );

		return $fields;
	}

    public function custom_predefined_fields_hook_visitors( $predefined_fields ) {

        $predefined_fields['vv_last_activity'] = array(
                                            'title'           => __( 'Last activity','um-visitors' ),
                                            'metakey'         => 'vv_last_activity',
                                            'type'            => 'date',
                                            'label'           => __( 'Last activity','um-visitors' ),
                                            'required'        => 0,
                                            'public'          => 1,
                                            'editable'        => false,
                                            'edit_forbidden'  => 1,
                                            'pretty_format' => 1,
                                            'years'         => 115,
                                            'years_x'       => 'past',
                                        );

        $predefined_fields['vv_last_logout'] = array(
                                            'title'           => __( 'Last logout','um-visitors' ),
                                            'metakey'         => 'vv_last_logout',
                                            'type'            => 'date',
                                            'label'           => __( 'Last logout','um-visitors' ),
                                            'required'        => 0,
                                            'public'          => 1,
                                            'editable'        => false,
                                            'edit_forbidden'  => 1,
                                            'pretty_format' => 1,
                                            'years'         => 115,
                                            'years_x'       => 'past',
                                        );

        $predefined_fields['vv_last_update'] = array(
                                            'title'           => __( 'Last update','um-visitors' ),
                                            'metakey'         => 'vv_last_update',
                                            'type'            => 'date',
                                            'label'           => __( 'Last update','um-visitors' ),
                                            'required'        => 0,
                                            'public'          => 1,
                                            'editable'        => false,
                                            'edit_forbidden'  => 1,
                                            'pretty_format' => 1,
                                            'years'         => 115,
                                            'years_x'       => 'past',
                                        );

        $predefined_fields['vv_visits_counter'] = array(
                                            'title'           => __( 'Number of visits','um-visitors' ),
                                            'metakey'         => 'vv_visits_counter',
                                            'type'            => 'text',
                                            'label'           => __( 'Visits','um-visitors' ),
                                            'required'        => 0,
                                            'public'          => 1,
                                            'editable'        => false,
                                            'edit_forbidden'  => 1,
                                        );

        $predefined_fields['vv_visitors_counter'] = array(
                                            'title'           => __( 'Number of visitors','um-visitors' ),
                                            'metakey'         => 'vv_visitors_counter',
                                            'type'            => 'text',
                                            'label'           => __( 'Visitors','um-visitors' ),
                                            'required'        => 0,
                                            'public'          => 1,
                                            'editable'        => false,
                                            'edit_forbidden'  => 1,
                                        );

        return $predefined_fields;
    }

}

new Visitors_Admin();
