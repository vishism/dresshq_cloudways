(function ($) {
    var emailInput;

    // function for validate email address
    function validateEmail(email) {
        var pattern = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
        return $.trim(email).match(pattern) ? true : false;
    }

    if (typeof rm_email_popup_enabled !== 'undefined' && rm_email_popup_enabled) {
        var addToCartButton = $(document).find(".add_to_cart_button, .single_add_to_cart_button, .ajax_add_to_cart");
        var ignoreEmailInput = false;

        if (addToCartButton.length) {
            addToCartButton.on('click', function (e) {
                var sessionEmailInput = localStorage.getItem('emailInput');
                if (sessionEmailInput || ignoreEmailInput) {
                    ignoreEmailInput = false;
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                $(".rm-email-popup-wrap").remove();

                var popupHtml = '<div class="rm-email-popup-wrap">' +
                    '   <div class="rm-email-popup" id="rm-email-popup">' +
                    '       <h3 class="rm-email-popup-title" id="rm-email-popup-title">' + rm_email_popup_text + '</h3>' +
                    '       <form class="input-container">' +
                    '           <input class="input-field" type="email" placeholder="Email" name="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">' +
                    '           <div class="bottom_text">' +
                    '             <a href="#" class="rm-email-popup-dismiss"  id="rm-email-popup-dismiss">' + rm_email_dismiss_text + '</a>' +
                    '             <a href="#" class="rm-email-popup-continue" id="rm-email-popup-continue">' + rm_email_continue_text + '</a>' +
                    '           </div>';
                '       </form>';
                if (rm_email_opt_in_text.length) {
                    popupHtml += '  <div class="rm-email-popup-opt-in">' + rm_email_opt_in_text + '</div>';
                }
                popupHtml += '</div></div>';

                if (!$(this).parent().is(".wrap-button")) {
                    $(this).wrap('<div class="wrap-button"></div>');
                }
                $(this).closest('.wrap-button').append(popupHtml);
            });

            $(document).on('change', 'form.input-container input', function () {
                emailInput = $(this).val();
                if (validateEmail(emailInput)) {
                    $(this).css({
                        color: "#555",
                        border: "1px solid green"
                    });
                } else {
                    $(this).css({
                        color: "red",
                        border: "1px solid red"
                    });
                }
            });

            $(document).on('submit', 'form.input-container', function (e) {
                e.preventDefault();
                $(this).find(".rm-email-popup-continue").trigger("click");
            });

            $(document).on('keypress', 'form.input-container', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).find(".rm-email-popup-continue").trigger("click");
                }
            })

            $(document).on('click', 'form.input-container .rm-email-popup-continue', function () {
                $('form.input-container input').trigger('change');
                if (!validateEmail(emailInput)) {
                    alert('Please enter valid email address');
                    return false;
                }

                // Identify using email address
                var _rmData = window._rmData || [];
                _rmData.push(['setCustomer', emailInput]);
                _rmData.push(['track', 'newsletter/subscribed', { "email": emailInput, "accepts_marketing": true, "tags": ["add-to-cart-widget"] }]);

                // Store email in localSorage
                localStorage.setItem("emailInput", emailInput);

                $(this).closest(".rm-email-popup-wrap").siblings(".add_to_cart_button, .single_add_to_cart_button, .ajax_add_to_cart").trigger("click");
                $(".rm-email-popup-wrap").remove();
                return true;
            });

            $(document).on('click', 'form.input-container .rm-email-popup-dismiss', function () {
                ignoreEmailInput = true;
                $(".rm-email-popup-wrap").siblings(".add_to_cart_button, .single_add_to_cart_button, .ajax_add_to_cart").trigger("click");
                $(".rm-email-popup-wrap").remove();
                return true;
            });
        }
    }
})(jQuery);
