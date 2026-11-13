/* jshint unused:vars, undef:true, browser:true, jquery:true */
/* global grecaptcha */

(function(global, $) {
    "use strict";

    function render(element) {
        var $element = $(element),
        clientId = grecaptcha.render($element.attr("id"), {
            sitekey: $element.data("sitekey"),
            badge: $element.data("badge"),
            theme: $element.data("theme"),
            size: "invisible"
        });
        grecaptcha.ready(function() {
            $element.attr("data-clientid", clientId);
        });
    }

    global.RecaptchaV3 = function() {
        $(".recaptcha-v3").each(function() {
            render(this);
        });
    };

    global.RecaptchaV3.execute = function($element, callback) {
        grecaptcha.execute($element.find('.recaptcha-v3').data("clientid"), {
            action: "submit"
        }).then(function(){
            if(callback) {
                callback();
            }
        });
    };
})(window, jQuery);
