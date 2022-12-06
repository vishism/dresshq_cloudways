jQuery('body').on('added_to_cart', function () {
    jQuery.ajax({
        url: '/?wc-ajax=get_cart_content',
        dataType: 'json',
        success: function (cart) {
            if (cart) {
                _rmData.push(["track", "carts/update", cart]);
            }
        }
    });
});
