<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class UM_Vistors_Modal {

    public $heading           = array();
    public $larger_modal_size = '<style>.um-admin-modal.larger {width:550px;margin-left:-450px;}</style>';
    public $date_local        = '';

    function __construct() {

        if ( is_admin()) {

            add_action( 'admin_footer',                                    array( $this, 'load_modal_user_visits' ), 9 );
            add_action( 'admin_footer',                                    array( $this, 'load_modal_user_visitors' ), 9 );
            add_filter( 'um_admin_user_row_actions',                       array( $this, 'um_admin_user_row_actions_user_visits' ), 10, 2 );
            add_filter( 'um_admin_user_row_actions',                       array( $this, 'um_admin_user_row_actions_user_visitors' ), 10, 2 );
            add_action( 'um_admin_ajax_modal_content__hook_user_visits',   array( $this, 'user_visits_ajax_modal' ));
            add_action( 'um_admin_ajax_modal_content__hook_user_visitors', array( $this, 'user_visits_ajax_modal' ));

            $this->heading = array(
                                    'vv_visits'   => __( 'User Profile Visits for %s', 'um-visitors' ),
                                    'vv_visitors' => __( 'User Profile Visitors for %s', 'um-visitors' ),
                                );

            $this->date_local = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        }
    }

    public function um_admin_user_row_actions_user_visits( $actions, $user_id ) {

        $actions['view_user_visits'] =    '<a href="javascript:void(0);" data-modal="UM_user_visits" 
                                          data-modal-size="larger" data-dynamic-content="user_visits" 
                                          data-arg1="' . esc_attr( $user_id ) . '" data-arg2="vv_visits">' .
                                          __( 'Visits', 'um-visitors' ) . '</a>';
        return $actions;
    }

    public function um_admin_user_row_actions_user_visitors( $actions, $user_id ) {

        $actions['view_user_visitors'] = '<a href="javascript:void(0);" data-modal="UM_user_visitors" 
                                          data-modal-size="larger" data-dynamic-content="user_visitors" 
                                          data-arg1="' . esc_attr( $user_id ) . '" data-arg2="vv_visitors">' .
                                          __( 'Visitors', 'um-visitors' ) . '</a>';
        return $actions;
    }

    public function load_modal_user_visits() {

        $this->load_modal_html_code( 'UM_user_visits', __( 'User Profile Visits', 'um-visitors' ));
    }

    public function load_modal_user_visitors() {

        $this->load_modal_html_code( 'UM_user_visitors', __( 'User Profile Visitors', 'um-visitors' ));
    }

    public function load_modal_html_code( $id, $hdr ) {

        echo $this->larger_modal_size; ?>
            <div id="<?php echo esc_attr( $id ); ?>" style="display:none">
                <div class="um-admin-modal-head">
                    <h3><?php echo esc_attr( $hdr ); ?></h3>
                </div>
                <div class="um-admin-modal-body"></div>
                <div class="um-admin-modal-foot"></div>
            </div>
<?php
   }

    public function user_visits_ajax_modal() {

        if ( isset( $_POST['arg1'] ) && ! empty( $_POST['arg1'] )) {
            $user_id = absint( sanitize_text_field( $_POST['arg1'] ));

            echo '<div class="um-admin-infobox">';
            if ( current_user_can( 'administrator' ) && um_can_view_profile( $user_id )) {

                if ( isset( $_POST['arg2'] ) && ! empty( $_POST['arg2'] )) {
                    $vv_type = sanitize_text_field( $_POST['arg2'] );

                    echo $this->get_modal_html( $user_id, $vv_type );
                }

            } else {

                echo '<p><label>' . __( 'No access', 'um-visitors' ) . '</label></p>';
            }
            echo '</div>';
        }
    }

    public function get_modal_html( $user_id, $vv_type ) {

        $user = get_user_by( 'id', $user_id );
        um_fetch_user( $user_id );

        ob_start(); ?>

        <div style="margin-left:15px;">
        <h2><?php echo sprintf( $this->heading[$vv_type], esc_attr( $user->user_login )); ?></h2>
        <?php
        $vv = new Visitors_Shortcodes();
        echo $vv->vv_show_activity_shortcode();
        echo '<hr>';
        echo $vv->vv_show_daily( $vv_type . '_counter', array( 'limit' => 7 ), __( 'Daily ( 7 last days )', 'um-visitors' ) );
        echo '<hr>';
        switch( $vv_type ) {
            case 'vv_visits':   echo $vv->vv_show_total_visits_shortcode(); break;
            case 'vv_visitors': echo $vv->vv_show_total_visitors_shortcode(); break;
        }
        echo '<hr>';

        $vv_array = um_user( $vv_type );
        if ( ! empty( $vv_array )) { ?>
            <table>
                <tr>
                    <th style="text-align:left;"><?php   _e( 'ID',       'um-visitors' ); ?></th>
                    <th style="text-align:center;"><?php _e( 'Username', 'um-visitors' ); ?></th>
                    <th style="text-align:left;"><?php   _e( 'Date',     'um-visitors' ); ?></th>
                </tr>
                <?php
                foreach( $vv_array as $uid => $time ) { 
                    $user = get_user_by( 'id', $uid );
                    $date = date_i18n( $this->date_local, $time );
                    ?>
                    <tr>
                        <td style="text-align:right;"><?php echo esc_attr( $uid ); ?></td>
                        <td style="text-align:right;"><?php echo esc_attr( $user->user_login ); ?></td>
                        <td style="text-align:right;"><?php echo esc_attr( $date ); ?></td>
                    </tr>
<?php           } ?>
            </table>

<?php   } else {
            switch( $vv_type ) {
                case 'vv_visits':   echo '<div>' . __( 'No visits data',   'um-visitors' ) . '</div>'; break;
                case 'vv_visitors': echo '<div>' . __( 'No visitors data', 'um-visitors' ) . '</div>'; break;
            }
        } ?>
        </div>
<?php
        return ob_get_clean();
    }

}

new UM_Vistors_Modal();
