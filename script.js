/*<![CDATA[*/
var ajaxData;
var ajaxUrl;
var ownPaymentUrl = '';
var operationId = '';
var pseudoAuthData = '';

(function ($) {

    jQuery.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        ajaxData = originalOptions.data;
        ajaxUrl = originalOptions.url;
    });

    jQuery(document).bind('ajaxStart', function (e, request, options) {
        if (ajaxUrl === wc_checkout_params.checkout_url && ajaxData.includes("payment_method=wooppay_wallet")) {
            $('#pleaseWaitDialog').modal({
                backdrop: 'static',
                keyboard: false
            });
            jQuery('#pleaseWaitDialog').css("display", 'flex');
        }
    });

    jQuery(document).bind('ajaxComplete', function (e, request, options) {
        if (ajaxUrl === wc_checkout_params.checkout_url && ajaxData.includes("payment_method=wooppay_wallet")) {
            if (jQuery('.woocommerce-error').length > 0) {
                jQuery('.woocommerce-error').remove()
            }
            if (JSON.parse(request.responseText).errorMessage) {
                $('#wooppay_frame_modal .modal-body').html(JSON.parse(request.responseText).view);
                $('#wooppay_frame_modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                closeLoaderGif()
                console.log(JSON.parse(request.responseText).errorMessage)
            }
            if (JSON.parse(request.responseText).step) {
                if (JSON.parse(request.responseText).step == 'pseudoAuth') {
                    operationId = JSON.parse(request.responseText).invoiceDTO.operationId;
                    pseudoAuth(JSON.parse(request.responseText).invoiceDTO)
                }
                if (JSON.parse(request.responseText).step == 'cards') {
                    renderCards(JSON.parse(request.responseText).cards)
                    $('#wooppay_choice_card_btn').on('click', function () {
                        $('#wooppay_frame_modal').modal('hide');
                        var form = $("form.checkout");
                        $.ajax({
                            url: wc_checkout_params.checkout_url,
                            type: "POST",
                            data: $("#wooppay_card_choice").serialize() + "&" + form.serialize() + "&pseudoAuth=" + JSON.stringify(JSON.parse(request.responseText).pseudoAuth) + "&step=payFromCard" + "&invoiceDTO=" + JSON.stringify(JSON.parse(request.responseText).invoiceDTO),
                            success: function (response) {
                                $('#wooppay_frame_modal .modal-body').html(response);
                                $('#wooppay_frame_modal').modal({
                                    backdrop: 'static',
                                    keyboard: false
                                });
                                closeLoaderGif()
                            },
                            error: function (response) {

                            }
                        });
                    })
                }
                if (JSON.parse(request.responseText).step == 'cardFrame') {
                    var frameUrl = JSON.parse(request.responseText).cardFrame.frameUrl;
                    var frame = `<iframe src= "${frameUrl}" width='600px' height='550px' style='border: none; width: 600px; height: 550px' frameborder='no' align='middle'> </iframe>`;
                    $('#wooppay_frame_modal .modal-body').html(frame);
                    $('#wooppay_frame_modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    closeLoaderGif()
                }
                if (JSON.parse(request.responseText).step == 'payFromCard') {
                    var invoiceDTO = JSON.stringify(JSON.parse(request.responseText).invoiceDTO);
                    var pseudoAuthData = JSON.stringify(JSON.parse(request.responseText).pseudoAuth);
                    var form = $("form.checkout");
                    $.ajax({
                        type: "POST",
                        url: wc_checkout_params.checkout_url,
                        data: form.serialize() + "&invoiceDTO=" + invoiceDTO + "&step=payFromCard&pseudoAuth=" + pseudoAuthData,
                        dataType: "json",
                        success: function (e) {
                        }
                    })
                }
            }
        }
    });

    function closeLoaderGif() {
        jQuery('#pleaseWaitDialog').css("display", 'none');
        jQuery('#pleaseWaitDialog').modal('hide');
    }

    function pseudoAuth(data) {
        var form = $("form.checkout");
        $.ajax({
            type: "POST",
            url: wc_checkout_params.checkout_url,
            data: form.serialize() + "&invoiceDTO=" + JSON.stringify(data) + "&step=pseudoAuth",
            dataType: "json",
            success: function (e) {
                pseudoAuthData = e.pseudoAuth;
                closeLoaderGif()
            }
        })
    }

    function renderCards(data) {
        $('#wooppay_frame_modal .modal-body').html(data);
        $('#wooppay_frame_modal').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function base64ToBlob(base64, mimetype) {
        if (!window.atob || !window.Uint8Array) {
            console.log('The current browser doesnot have the atob function. Cannot continue');
            return null;
        }
        slicesize = 512;
        var bytechars = atob(base64);
        var bytearrays = [];
        for (var offset = 0; offset < bytechars.length; offset += slicesize) {
            var slice = bytechars.slice(offset, offset + slicesize);
            var bytenums = new Array(slice.length);
            for (var i = 0; i < slice.length; i++) {
                bytenums[i] = slice.charCodeAt(i);
            }
            var bytearray = new Uint8Array(bytenums);
            bytearrays[bytearrays.length] = bytearray;
        }
        return new Blob(bytearrays, {type: mimetype});
    }

    var functionName = "rechargeReceiver";

    if (typeof functionName != "function") {
        functionName = function (event) {
            if (event.data) {
                var message = JSON.parse(event.data);
                console.log(message)
                if (message.status !== 4) {
                    var err_info = "";
                    if (message.data && typeof message.data.errorCode != "undefined") {
                        var errors_text = getAcquiringErrors();
                        var err_key = "e_" + message.data.errorCode;
                        console.log(err_key)
                        if (err_key in errors_text) {
                            err_info = errors_text[err_key];
                        } else {
                            err_info = 'Произошла ошибка. Скорее всего вы ввели некорректные данные';
                        }
                    }
                    if (message.status == 3) {
                        if (err_info == '') {
                            err_info = 'Произошла ошибка. Скорее всего вы ввели некорректные данные карты';
                        }
                    } else if (message.status == 2) {
                        if (err_info == '') {
                            err_info = 'Произошла ошибка. Возможно вы ввели некорректные данные карты';
                        }
                    }

                    if (JSON.parse(event.data) == '"OTP frame loaded!"') {
                        $('.modal-body>iframe').animate({height: 700}, 200);
                    }

                    if (message.status == 3 || message.status == 2) {
                        var form = $("form.checkout");
                        $.ajax({
                            type: "POST",
                            url: wc_checkout_params.checkout_url,
                            data: form.serialize() + "&step=failPayment&error=" + err_info,
                            dataType: "json",
                            success: function (e) {
                                $('#wooppay_frame_modal .modal-body').html(e.view);
                                $('#wooppay_frame_modal').modal({
                                    backdrop: 'static',
                                    keyboard: false
                                });
                                closeLoaderGif()
                            }
                        })
                    }

                    if (message.status == 1) {
                        var form = $("form.checkout");
                        $.ajax({
                            type: "POST",
                            url: wc_checkout_params.checkout_url,
                            data: form.serialize() + "&step=successPayment&operationId=" + operationId,
                            dataType: "json",
                            success: function (e) {
                                $('#wooppay_frame_modal .modal-body').html(e.view);
                                $('#wooppay_frame_modal').modal({
                                    backdrop: 'static',
                                    keyboard: false
                                });
                                $('#finish_url').on('click', finishPayment)
                                $('.close').on('click', finishPayment)
                                $("#wooppay_frame_modal").on("hide.bs.modal", finishPayment);
                                closeLoaderGif()
                            }
                        })
                    }

                    function finishPayment() {
                        var form = $("form.checkout");
                        $.ajax({
                            type: "POST",
                            url: wc_checkout_params.checkout_url,
                            data: form.serialize() + "&step=finish&operationId=" + operationId + "&pseudoAuth=" + JSON.stringify(pseudoAuthData),
                            success: function (e) {
                                console.log(e)
                                if (e.receipt) {
                                    var mime = 'application/pdf';
                                    var a = document.createElement('a');
                                    var urlCreator = window.URL || window.webkitURL || window.mozURL || window.msURL;
                                    if (urlCreator && window.Blob && ('download' in a) && window.atob) {
                                        var blob = base64ToBlob(e.receipt, mime);
                                        var url = window.URL.createObjectURL(blob);
                                        a.setAttribute('href', url);
                                        a.setAttribute("download", 'receipt.pdf');
                                        var event = document.createEvent('MouseEvents');
                                        event.initMouseEvent('click', true, true, window, 1, 0, 0, 0, 0, false, false, false, false, 0, null);
                                        a.dispatchEvent(event);
                                    }
                                    window.location = e.returnUrl;
                                } else {
                                    window.location = e.returnUrl;
                                }
                            }
                        })
                    }
                }
            }
        };
        window.addEventListener("message", functionName, false);
    }

    function getAcquiringErrors() {
        return {
            'e_04': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
            'e_05': 'Транзакция отклонена. Позвоните в Колл-центр вашего банка.',
            'e_07': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
            'e_12': 'Недействительная транзакция, перепроверьте введенные данные. В случае повторения ошибки попробуйте позже...',
            'e_14': 'Недействительный номер карты.',
            'e_19': 'Ошибка авторизации.',
            'e_30': 'Переданы неверные данные для оплаты пополнения. Обратитесь в службу поддержки.',
            'e_36': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
            'e_37': 'По карте выставлены ограничения. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
            'e_41': 'Карта, числится в базе утерянных. Позвоните в Колл-центр вашего банка.',
            'e_45': 'Карта, числится в базе украденых. Позвоните в Колл-центр вашего банка, либо обратиться в ближайшее отделение полиции.',
            'e_51': 'Недостаточно средств на карте.',
            'e_54': 'Истёк срок действия карты.',
            'e_57': 'Карта закрыта для интернет-транзакций. Обратитесь в ваш банк.',
            'e_58': 'Операции с картами временно приостановлены. Попробуйте позже.',
            'e_61': 'Сумма превышает допустимый суточный лимит. Можете обратиться в службу поддержки, либо завершить операцию завтра.',
            'e_62': 'Карта заблокирована банком. Позвоните в Колл-центр вашего банка.',
            'e_91': 'Ваш банк временно не доступен. Попробуйте оплатить позже.',
            'e_96': 'Не установлен 3DSecure(SecureCode) либо сбой связи. Позвоните в Колл-центр вашего банка.',
        };
    }

})(jQuery);
/*]]>*/