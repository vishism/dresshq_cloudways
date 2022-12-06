<?php

class RemarketyRecoverCart
{
    function init()
    {
        add_action('template_redirect', array($this, 'include_template'));
        add_filter('init', array($this, 'rewrite_rules'));
    }

    //https://remarkety-woo.local/?remarkety_recover_cart=17:2;24:1;63:1
    public function include_template($template)
    {
        $recover_cart = get_query_var('remarkety_recover_cart');

        if ($recover_cart) {
            $products = $this->parseRecoverProducts($recover_cart);
            if (count($products) > 0) {
                $this->addToCart($products);

                wp_redirect(wc_get_cart_url());
                exit();
            }
        }

        return $template;
    }

    /**
     * 17:2;2:1
     * product id = 17 quantity = 2
     * product id = 2 quantity = 1
     * @param $recover_cart_string
     * @return array
     */
    private function parseRecoverProducts($recover_cart_string)
    {
        $products = [];
        $products_str = explode(';', $recover_cart_string);
        foreach ($products_str as $pstr) {
            $product = explode(':', $pstr);
            if (count($product) === 2) {
                $product_id = (int)$product[0];
                $product_qt = (int)$product[1];
                if (is_integer($product_id) && is_integer($product_qt)) {
                    $products[$product_id] = $product_qt;
                }
            }
        }

        return $products;
    }

    private function addToCart($products)
    {
        foreach ($products as $product_id => $product_qt) {
            $cart_product_id = WC()->cart->generate_cart_id($product_id);
            $in_cart = WC()->cart->find_product_in_cart($cart_product_id);

            if (!$in_cart) {
                if (!$this->isVariations($product_id)) {
                    WC()->cart->add_to_cart($product_id, $product_qt);
                }
            }
        }
    }

    private function isVariations($product_id) {
        $cart = WC()->cart->get_cart_contents();
        $variations = [];
        $is_variation = false;

        foreach ($cart as $key => $item) {
            if (!empty($item['variation_id'])) {
                $variations[$key] = [
                    'str_key' => $key,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id']
                ];
            }
        }

        foreach ($variations as $variation) {
            if ($variation['variation_id'] == $product_id) {
                $is_variation = true;
                break;
            }
        }

        return $is_variation;
    }

    public function flush_rules()
    {
        $this->rewrite_rules();

        flush_rewrite_rules();
    }

    public function rewrite_rules()
    {
        add_rewrite_tag('%remarkety_recover_cart%', '([^&]+)');
    }
}
