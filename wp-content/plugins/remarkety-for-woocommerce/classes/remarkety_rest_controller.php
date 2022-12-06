<?php
class RM_REST_controller {
    const RM_API_NAMESPACE = 'wc/v2';

    const TRACING_ENDPOINT = 'tracking';

    public function set_tracking_id (WP_REST_Request $request) {
        $tracking_id = $request->get_param('store');

        $old_tracking_id = get_option(remarkety_for_woocommerce::OPTION_WEBTRACKING_ID);
        if ($old_tracking_id) {
            update_option(remarkety_for_woocommerce::OPTION_WEBTRACKING_ID, $tracking_id);
            remarkety_for_woocommerce::log("wc_rest_controller updated webtracking id : " . $tracking_id);
        } else {
            add_option(remarkety_for_woocommerce::OPTION_WEBTRACKING_ID, $tracking_id);
            remarkety_for_woocommerce::log("wc_rest_controller added webtracking id : " . $tracking_id);
        }

        return true;
    }

    public function register_routes() {
        register_rest_route(
            self::RM_API_NAMESPACE,
            '/' . self::TRACING_ENDPOINT,
            array(
                'methods' => 'PUT',
                'callback' => array( $this, 'set_tracking_id' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_woocommerce' );
                }
            )
        );
    }
}