<?php
class WordCamp_Budget_Tool {
    public static function load() {
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_filter( 'heartbeat_received', array( __CLASS__, 'heartbeat_received' ), 10, 2 );
        add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
    }

    public static function admin_menu() {
		add_submenu_page( 'wordcamp-budget', __( 'WordCamp Budget', 'wordcamporg' ), __( 'Budget', 'wordcamporg' ), 'manage_options', 'wordcamp-budget' );
        add_action( 'wcb_render_budget_page', array( __CLASS__, 'render' ) );
        register_setting( 'wcb_budget_noop', 'wcb_budget_noop', array( __CLASS__, 'validate' ) );
    }

    public static function validate( $noop ) {
        if ( empty( $_POST['_wcb_budget_data'] ) )
            return;

        if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wcb_budget_noop-options' ) )
            return;

        $budget = self::_get_budget();
        $data = json_decode( wp_unslash( $_POST['_wcb_budget_data'] ), true );

        $valid_attributes = array( 'type', 'category', 'amount', 'note', 'link', 'name', 'value' );
        foreach ( $data as &$item ) {
            $_item = array();
            foreach ( $item as $key => $value ) {
                if ( ! in_array( $key, $valid_attributes ) )
                    continue;

                if ( $key == 'amount' )
                    $value = round( floatval( $value ), 2 );

                $_item[ $key ] = $value;
            }

            $item = $_item;
        }

        if ( $budget['status'] == 'draft' && ! empty( $_POST['wcb-budget-save-draft'] ) ) {
            $budget['prelim'] = $data;
        } elseif ( $budget['status'] == 'draft' && ! empty( $_POST['wcb-budget-submit'] ) ) {
            $budget['prelim'] = $data;
            $budget['status'] = 'pending';
        } elseif ( $budget['status'] == 'pending' && current_user_can( 'wcb_approve_budget' ) ) {
            if ( ! empty( $_POST['wcb-budget-reject'] ) ) {
                $budget['status'] = 'draft';
            } elseif ( ! empty( $_POST['wcb-budget-approve'] ) ) {
                $budget['status'] = 'approved';
                $budget['approved_by'] = get_current_user_id();

                // Clone the approved prelim. budget.
                $budget['approved'] = $budget['prelim'];
                $budget['working'] = $budget['prelim'];
            }
        } elseif ( $budget['status'] == 'approved' && ! empty( $_POST['wcb-budget-update-working'] ) ) {
            $budget['working'] = $data;
        } elseif ( $budget['status'] == 'approved' && ! empty( $_POST['wcb-budget-reset'] ) ) {
            $budget['working'] = $budget['approved'];
        }

        $budget['updated'] = time();
        $budget['updated_by'] = get_current_user_id();

        update_option( 'wcb_budget', $budget, 'no' );
        return;
    }

    public static function enqueue_scripts() {
        $screen = get_current_screen();
        if ( $screen->id == 'toplevel_page_wordcamp-budget' ) {
            wp_enqueue_script( 'wcb-budget-tool',
                plugins_url( 'javascript/budget-tool.js', __DIR__ ),
                array( 'backbone', 'jquery', 'jquery-ui-sortable', 'heartbeat', 'underscore' ), 2 , true );
        }
    }

    private static function _get_budget() {
        $budget = get_option( 'wcb_budget', array(
            'status' => 'draft',
            'prelim' => self::_get_default_budget(),
        ) );

        return $budget;
    }

    private static function _get_default_budget() {
        return array(
            array( 'type' => 'meta', 'name' => 'attendees', 'value' => 300 ),
            array( 'type' => 'meta', 'name' => 'days', 'value' => 2 ),
            array( 'type' => 'meta', 'name' => 'tracks', 'value' => 4 ),
            array( 'type' => 'meta', 'name' => 'speakers', 'value' => 25 ),
            array( 'type' => 'meta', 'name' => 'volunteers', 'value' => 10 ),
            array( 'type' => 'meta', 'name' => 'currency', 'value' => 'USD' ),
            array( 'type' => 'meta', 'name' => 'ticket-price', 'value' => 20.00 ),

            array( 'type' => 'income', 'category' => 'other', 'note' => 'Tickets Income', 'amount' => 0, 'link' => 'ticket-price-x-attendees' ),
            array( 'type' => 'income', 'category' => 'other', 'note' => 'Community Sponsorships', 'amount' => 4300 ),
            array( 'type' => 'income', 'category' => 'other', 'note' => 'Local Sponsorships', 'amount' => 7000 ),
            array( 'type' => 'income', 'category' => 'other', 'note' => 'Microsponsors', 'amount' => 500 ),

            array( 'type' => 'expense', 'category' => 'venue', 'note' => 'Venue', 'amount' => 7500 ),
            array( 'type' => 'expense', 'category' => 'venue', 'note' => 'Wifi Costs', 'amount' => 300, 'link' => 'per-day' ),
            array( 'type' => 'expense', 'category' => 'other', 'note' => 'Comped Tickets', 'amount' => 300 ),
            array( 'type' => 'expense', 'category' => 'audio-visual', 'note' => 'Video recording', 'amount' => 500 ),
            array( 'type' => 'expense', 'category' => 'audio-visual', 'note' => 'Projector rental', 'amount' => 300 ),
            array( 'type' => 'expense', 'category' => 'audio-visual', 'note' => 'Livestream', 'amount' => 200 ),
            array( 'type' => 'expense', 'category' => 'signage-badges', 'note' => 'Printing', 'amount' => 800 ),
            array( 'type' => 'expense', 'category' => 'signage-badges', 'note' => 'Badges', 'amount' => 8.21, 'link' => 'per-attendee' ),
            array( 'type' => 'expense', 'category' => 'food-beverage', 'note' => 'Snacks', 'amount' => 300 ),
            array( 'type' => 'expense', 'category' => 'food-beverage', 'note' => 'Lunch', 'amount' => 2350 ),
            array( 'type' => 'expense', 'category' => 'food-beverage', 'note' => 'Coffee', 'amount' => 500 ),
            array( 'type' => 'expense', 'category' => 'swag', 'note' => 'T-shirts', 'amount' => 780 ),
            array( 'type' => 'expense', 'category' => 'speaker-event', 'note' => 'Speakers Dinner', 'amount' => 20, 'link' => 'per-speaker' ),
        );
    }

    public static function render() {
        $budget = self::_get_budget();

        $view = ! empty( $_GET['wcb-view'] ) ? $_GET['wcb-view'] : 'prelim';
        if ( ! in_array( $view, array( 'prelim', 'working', 'approved' ) ) )
            $view = 'prelim';

        if ( $view == 'prelim' && $budget['status'] == 'approved' )
            $view = 'approved';

        $editable = false;
        if ( $view == 'prelim' && $budget['status'] == 'draft' ) {
            $editable = true;
        } elseif ( $view == 'working' && $budget['status'] == 'approved' ) {
            $editable = true;
        }

        if ( ! $inspire_urls = get_site_transient( 'wcb-inspire-urls' ) ) {
            $urls = array( 'https://jawordpressorg.github.io/wapuu/wapuu-archive/original-wapuu.png' );
            $r = wp_remote_get( 'https://jawordpressorg.github.io/wapuu-api/v1/wapuu.json' );
            if ( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) == 200 ) {
                $body = json_decode( wp_remote_retrieve_body( $r ), true );
                $maybe_urls = wp_list_pluck( wp_list_pluck( $body, 'wapuu' ), 'src' );
                if ( count( $maybe_urls ) > 0 ) {
                    $inspire_urls = $maybe_urls;
                }
            }

            set_site_transient( 'wcb-inspire-urls', $inspire_urls, 30 * DAY_IN_SECONDS );
        }

        $currencies = WordCamp_Budgets::get_currencies();
        foreach ( $currencies as $key => $value )
            if ( substr( $key, 0, 4 ) == 'null' )
                unset( $currencies[ $key ] );

        ksort( $currencies );

        require( dirname( __DIR__ ) . '/views/budget-tool/main.php' );
    }

    public static function heartbeat_received( $response, $data ) {
        if ( empty( $data['wcb_budgets_heartbeat'] ) )
            return $response;

        $response['wcb_budgets'] = array(
            'nonce' => wp_create_nonce( 'wcb_budget_noop-options' ),
        );

        return $response;
    }

    public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
        global $trusted_deputies;

        if ( $cap == 'wcb_approve_budget' ) {
            if ( user_can( $user_id, is_multisite() ? 'manage_network' : 'manage_options' ) ) {
                $caps = array( 'exist' );
            } elseif ( in_array( $user_id, (array) $trusted_deputies ) ) {
                $caps = array( 'exist' );
            }
        }

        return $caps;
    }
}

WordCamp_Budget_Tool::load();