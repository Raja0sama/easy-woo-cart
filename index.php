<?php
/*
Plugin Name: easy-woo-cart 🔥
Plugin URI:  https://www.rajaosama.me
Description: Use this plugin, it will allow you to add items to the cart by their id, which then will redirect you to direct cart.
Version:     1.0
Author:      Raja Osama
*/



function woocommerce_maybe_add_multiple_products_to_cart()
{
    // Make sure WC is installed, and add-to-cart qauery arg exists, and contains at least one comma.
    if (!class_exists('WC_Form_Handler') || empty($_REQUEST['add-to-cart']) || false === strpos($_REQUEST['add-to-cart'], ',')) {
        return;
    }

    // Remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
    remove_action('wp_loaded', array('WC_Form_Handler', 'add_to_cart_action'), 20);


    $req = $_REQUEST['add-to-cart'];

    $products = json_decode(urldecode(stripslashes($req)));
    $count       = count($products);
    $number      = 0;




    foreach ($products as $product) {

        $product_id = $product->item;
        $quantity = $product->quantity;


        $product_id        = apply_filters('woocommerce_add_to_cart_product_id', absint($product_id));
        $was_added_to_cart = false;
        $adding_to_cart    = wc_get_product($product_id);

        if (!$adding_to_cart) {
            continue;
        }

        $add_to_cart_handler = apply_filters('woocommerce_add_to_cart_handler', $adding_to_cart->product_type, $adding_to_cart);


        if ($number == 0 && isset($_REQUEST['clearCart'])) {
            add_filter('woocommerce_add_to_cart_validation', 'remove_cart_item_before_add_to_cart', 20, 3);
        }
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
        if ($number == 0 && isset($_REQUEST['clearCart'])) {
            remove_action('woocommerce_add_to_cart_validation', 'remove_cart_item_before_add_to_cart', 20, 3);
        }
        $fakeqty = $quantity;
        if ($number == $count - 1) {

            $fakeqty = $quantity - 1;
        }
        if ($passed_validation && false !== WC()->cart->add_to_cart($product_id, $fakeqty)) {
            wc_add_to_cart_message(array($product_id => $fakeqty), true);
        }
        if (++$number === $count) {
            // Ok, final item, let's send it back to woocommerce's add_to_cart_action method for handling.
            $_REQUEST['add-to-cart'] = $product_id;

            return WC_Form_Handler::add_to_cart_action();
        }
    }
}
function remove_cart_item_before_add_to_cart($passed, $product_id, $quantity)
{
    if (!WC()->cart->is_empty())
        WC()->cart->empty_cart();
    return $passed;
}
add_action('wp_loaded',        'woocommerce_maybe_add_multiple_products_to_cart', 15);
