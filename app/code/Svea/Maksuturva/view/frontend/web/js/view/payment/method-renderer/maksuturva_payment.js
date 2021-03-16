define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/get-totals',
        'mage/validation'
    ],
    function ($, ko, Component, selectPaymentMethodAction,quote, checkoutData, urlBuilder, url,customer, additionalValidators, placeOrderAction, fullScreenLoader, getTotalsAction) {
        'use strict';

        var selectedMethod = ko.observable();
        var selectedSveaMethod = checkoutData.getSelectedPaymentMethod();

        return Component.extend({
            //be implemented in sub-class
            getCode: function() {},

            getPaymentMethodsList: function () {
                var apiSellerId = window.checkoutConfig.payment['maksuturva_generic_payment']['api_sellerid'];
                var apiHost = window.checkoutConfig.payment['maksuturva_generic_payment']['api_host'];

                if (!apiSellerId || !apiHost) {
                    console.warn("Unable to get API sellerid or host information.");
                    return;
                } else {
                    //console.log("### API HOST: " + apiSellerId + ", " + apiHost);
                }
                var bannerCss = ".maksuturva---banner-container__outer{display:block}.maksuturva---banner-container{overflow:hidden;background-color:white;margin:1rem;display:-ms-inline-flexbox;display:inline-flex;-ms-flex-direction:column;flex-direction:column}.maksuturva---banner-container.maksuturva---banner-container-drop-shadow{box-shadow:0 1px 0px 1px rgba(0,0,0,0.1),1px 1px 7px rgba(0,0,0,0.1)}.maksuturva---banner-container.maksuturva---banner-container-rounded-corners{border-radius:0.2rem}.maksuturva---banner-container.maksuturva---banner-container-bordered{box-sizing:border-box}.maksuturva---banner-container.maksuturva---banner-container-bordered .maksuturva---banner__inner-mid{border:1px solid #e2e2e2;border-top:none}.maksuturva---banner__inner-top{background-color:#CCEFF5;#42ab3a;height:auto;padding:0.5rem 0.5rem;display:-ms-flexbox;display:flex;-ms-flex-align:center;align-items:center;-ms-flex-pack:justify;justify-content:space-between;-ms-flex-wrap:wrap;flex-wrap:wrap}.maksuturva---banner__inner-top p{color:#ffffff;text-align:center}.maksuturva---banner__inner-mid{display:-ms-flexbox;display:flex;-ms-flex-wrap:wrap;flex-wrap:wrap;-ms-flex-align:center;align-items:center;-ms-flex-pack:start;justify-content:flex-start;padding:0.5rem}.maksuturva---banner__inner-bottom{padding:0.25rem 0.5rem;text-align:center;font-size:0.75rem;font-weight:bold}.maksuturva---banner__logo{height:1.25rem !important;width:auto}.maksuturva---banner__icon-container{padding:0.125rem;max-width:100%;margin-left:auto;margin-right:auto}.maksuturva---banner__icon-container:last-child{margin-left:0;-ms-flex-positive:1;flex-grow:1}.maksuturva---banner__icon-container .maksuturva---banner__icon{width:auto;max-height:40px}.maksuturva---banner-container-mobile .maksuturva---banner__inner-top{height:auto;-ms-flex-pack:center;justify-content:center}@media screen and (max-width: 200px){.maksuturva---banner__icon-container:last-child{margin-left:auto;-ms-flex-positive:0;flex-grow:0}}";
                var darkBannerLogo = "https://payments.maksuturva.fi/pmt/2019/img/prod/company/Svea_logo.svg";
                var lightBannerLogo = "https://payments.maksuturva.fi/pmt/2019/img/prod/company/Svea_logo_white.svg";

                function generateBannerClasses(options) {
                    var bannerContainerClasses = "maksuturva---banner-container";
                    //if (options.dropShadow) {
                        bannerContainerClasses += " maksuturva---banner-container-drop-shadow";
                    //}
                    //if (options.roundedCorners) {
                        bannerContainerClasses +=
                            " maksuturva---banner-container-rounded-corners";
                    //}
                    //if (options.bordered) {
                        bannerContainerClasses += " maksuturva---banner-container-bordered";
                   // }
                    return bannerContainerClasses;
                }

                function generateBannerContent(methods, options) {
                    var methodContents = [];
                    for (var i = 0, l = methods.length; i < l; i++) {
                        var method = methods[i];
                        methodContents.push(
                            '<div class="maksuturva---banner__icon-container"><img src="' +
                            method.imageurl +
                            '" class="maksuturva---banner__icon" alt="' +
                            method.displayname +
                            '"></div>'
                        );
                    }
                    var methodHtml = methodContents.join("");
                    var bannerInnerTopAttributes = "";
                    var bannerLogo;
                    if (options.topBackgroundColor) {
                        bannerInnerTopAttributes =
                            " style='background-color: " + options.topBackgroundColor + "'";
                        bannerLogo = isColorDark(options.topBackgroundColor) ?
                            lightBannerLogo :
                            darkBannerLogo;
                    } else {
                        bannerLogo = darkBannerLogo;
                    }

                    return (
                        '<div class="maksuturva---banner__inner-top"' +
                        bannerInnerTopAttributes +
                        '><img class="maksuturva---banner__logo" src="' +
                        bannerLogo +
                        '" alt="Maksuturva logo"></div><div class="maksuturva---banner__inner-mid">' +
                        methodHtml +
                        "</div>"
                    );
                }

                function processPaymentMethods(paymentMethods) {
                    var methods = [];
                    for (var i = 0, l = paymentMethods.length; i < l; i++) {
                        var paymentMethod = paymentMethods[i];
                        var code = paymentMethod.getElementsByTagName("code")[0].innerHTML;
                        // Resurs Bank "order new account" -method logo will be skipped 
                        if ("RBRC" != code) {
                            var imageurlNode = paymentMethod.getElementsByTagName("imageurl")[0];
                            if (imageurlNode) {
                                var displaynameNode = paymentMethod.getElementsByTagName("displayname")[0];
                                methods.push({
                                    imageurl: imageurlNode.firstChild.nodeValue,
                                    displayName: displaynameNode && displaynameNode.firstChild.nodeValue,
                                });
                            }
                        }
                    }
                    return methods;
                }

                function fetchPaymentMethods(options, callback) {
                    var url =
                        apiHost +
                        "/GetPaymentMethods.pmt?sellerid=" +
                        apiSellerId;

                    if (options.requestLocale) {
                        url += "&request_locale=" + options.requestLocale;
                    }
                    if (options.totalamount) {
                        url += "&totalamount=" + options.totalamount;
                    }
                    var request = new XMLHttpRequest();
                    request.open("GET", url);
                    request.onload = function () {
                        var data = request.responseXML;
                        var errorElement = data.getElementsByTagName("ERROR")[0];
                        if (errorElement) {
                            console.warn(errorElement.innerHTML);
                        } else {
                            var paymentMethods = data.getElementsByTagName("paymentmethod");
                            callback(processPaymentMethods(paymentMethods));
                        }
                    };
                    request.onerror = function () {
                        console.warn("Error calling Maksuturva API.");
                    };
                    request.send();
                }

                function injectCss() {
                    if (!document.querySelector(".maksuturva---styles")) {
                        var style = document.createElement("style");
                        style.className = "maksuturva---styles";
                        style.innerHTML = bannerCss;
                        document.body.appendChild(style);
                    }
                }

                function insertBanner(bannerLocation, callback) {
                    var container = document.createElement("div");
                    var div = container.appendChild(document.createElement("div"));
                    var paymentMethodOptions = {
                        requestLocale: bannerLocation.getAttribute("data-request-locale"),
                        totalamount: bannerLocation.getAttribute("data-totalamount"),
                    };
                    fetchPaymentMethods(paymentMethodOptions, function (methods) {
                        var options = {
                            topBackgroundColor: "#05799c"
                        };
                        container.setAttribute("class", "maksuturva---banner-container__outer");
                        div.setAttribute("class", generateBannerClasses(options));
                        div.innerHTML = generateBannerContent(methods, options);
                        callback(
                            bannerLocation.parentNode.insertBefore(
                                container,
                                bannerLocation.nextSibling
                            )
                        );
                    });
                }

                function flexBoxSupported() {
                    var doc = document.body || document.documentElement;
                    var style = doc.style;
                    return (
                        style.webkitFlexWrap == "" ||
                        style.msFlexWrap == "" ||
                        style.flexWrap == ""
                    );
                }

                function initializeBanners(done) {
                    if (!flexBoxSupported()) {
                        return;
                    }
                    var bannerLocations = document.querySelectorAll(".maksuturva-banner");

                    if (bannerLocations.length > 0) {
                        injectCss();
                    }
                    for (var i = 0, l = Maksuturva.banners.length; i < l; i++) {
                        var el = Maksuturva.banners[i];
                        el.parentNode.removeChild(el);
                    }
                    Maksuturva.banners = [];
                    var bannersLoaded = 0;
                    for (var i = 0, l = bannerLocations.length; i < l; i++) {
                        var bannerLocation = bannerLocations[i];
                        insertBanner(bannerLocation, function (el) {
                            bannersLoaded += 1;
                            Maksuturva.banners.push(el);

                            if (bannersLoaded === bannerLocations.length) {
                                done && done();
                            }
                        });
                    }
                }

                function isColorDark(c) {
                    var c = c; // strip #
                    if (c == "false") { // c start false some reason, first time use svea blue. Color picker works and next time go to else.
                        var c = "#CCEFF5";
                        var c = c.substring(1); // strip #
                        var rgb = parseInt(c, 16); // convert rrggbb to decimal
                        var r = (rgb >> 16) & 0xff; // extract red
                        var g = (rgb >> 8) & 0xff; // extract green
                        var b = (rgb >> 0) & 0xff; // extract blue
                        var luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
                        return luma < 100;
                    } else {
                        var c = c.substring(1); // strip #
                        var rgb = parseInt(c, 16); // convert rrggbb to decimal
                        var r = (rgb >> 16) & 0xff; // extract red
                        var g = (rgb >> 8) & 0xff; // extract green
                        var b = (rgb >> 0) & 0xff; // extract blue
                        var luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
                        return luma < 100;
                    }
                }

                function updateContainerWidths() {
                    for (var i = 0, l = Maksuturva.banners.length; i < l; i++) {
                        var banner = Maksuturva.banners[i];

                        if (banner.offsetWidth < 480) {
                            banner.classList.add("maksuturva---banner-container-mobile");
                        } else {
                            banner.classList.remove("maksuturva---banner-container-mobile");
                        }
                    }
                }

                var Maksuturva = {
                    initialLoadDone: false,
                    initializeBanners: initializeBanners,
                    banners: [],
                };
                window.Maksuturva = Maksuturva;

                if (!window.Maksuturva.initialLoadDone) {
                    window.Maksuturva.initialLoadDone = true;
                    initializeBanners(function () {
                        updateContainerWidths();
                    });
                }

                document.addEventListener("maksuturva-load-banner", function (event) {
                    if (!window.Maksuturva.initialLoadDone) {
                        window.Maksuturva.initialLoadDone = true;
                        initializeBanners(function () {
                            updateContainerWidths();
                        });
                    }
                });

                window.addEventListener("resize", resizeThrottler, false);
                var resizeTimeout;
                function resizeThrottler() {
                    if (!resizeTimeout) {
                        resizeTimeout = setTimeout(function () {
                            resizeTimeout = null;
                            resizeHandler();
                            // The resizeHandler will execute at a rate of 10 fps
                        }, 100);
                    }
                }

                function resizeHandler() {
                    updateContainerWidths();
                }
                return "";
            },
            getData: function () {
                return {
                    "method": this.item.method,
                    "extension_attributes": {maksuturva_preselected_payment_method : this.selectedPayment()}
                };

            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            afterPlaceOrder: function () {
                window.location.href = url.build('maksuturva/index/redirect/?timestamp='+ new Date().getTime() +'');
            },

            validate: function () {
                var form = 'form[data-role=maksuturva_base_payment-form]';

                if(this.isPreselectRequired() && (this.selectedPayment() === undefined || this.selectedPayment() === null)){
                    return false;
                }
                return $(form).validation() && $(form).validation('isValid');
            },

            selectedPayment: selectedMethod,

            selectSubMethod : function (data, event){
                this.selectedPayment(event.target.value);
                return true;
            },

            //it is EASY TO HACK in this coding way, but it doesn't matter in case
            isPreselectRequired: function (){
                return window.checkoutConfig.payment[this.getCode()]['preselectRequired'];
            }
        });
    }
);