(function ($) {
    'use strict';

    var request = function (action, data) {
        return $.ajax({
            url: TAQL.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: $.extend({ action: 'taql_' + action, nonce: TAQL.nonce }, data || {})
        });
    };

    var messageFrom = function (response) {
        return response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.message
            ? response.responseJSON.data.message
            : TAQL.i18n.error;
    };

    var toast = function (html, type) {
        $('.taql-toast').remove();
        var $toast = $('<div class="taql-toast" role="status"></div>').addClass(type || '').html(html);
        $('body').append($toast);
        window.setTimeout(function () { $toast.addClass('is-visible'); }, 10);
        window.setTimeout(function () { $toast.removeClass('is-visible'); }, 6500);
    };

    var replaceList = function (html) {
        $('#taql-app').html(html);
    };

    $(document).on('click', '.taql-add-button', function (event) {
        event.preventDefault();
        var $button = $(this);
        var $form = $button.closest('form.cart');
        var variationId = parseInt($form.find('input[name="variation_id"]').val(), 10) || 0;
        var quantity = parseInt($form.find('input.qty').val(), 10) || 1;

        $button.prop('disabled', true).addClass('loading');
        request('add', {
            product_id: $button.data('product-id'),
            variation_id: variationId,
            quantity: quantity
        }).done(function (response) {
            if (!response.success) {
                toast(response.data.message || TAQL.i18n.error, 'is-error');
                return;
            }
            toast(TAQL.i18n.added + ' <a href="' + response.data.listUrl + '">' + TAQL.i18n.viewList + '</a>', 'is-success');
            $(document.body).trigger('taql_item_added', [response.data.count]);
        }).fail(function (response) {
            toast(messageFrom(response), 'is-error');
        }).always(function () {
            $button.prop('disabled', false).removeClass('loading');
        });
    });

    var quantityTimer;
    $(document).on('input change', '.taql-quantity', function () {
        var $input = $(this);
        var quantity = Math.max(1, parseInt($input.val(), 10) || 1);
        $input.val(quantity);
        window.clearTimeout(quantityTimer);
        quantityTimer = window.setTimeout(function () {
            request('update', { key: $input.closest('tr').data('key'), quantity: quantity })
                .done(function (response) { if (response.success) { replaceList(response.data.html); } })
                .fail(function (response) { toast(messageFrom(response), 'is-error'); });
        }, 450);
    });

    $(document).on('click', '.taql-remove', function () {
        var key = $(this).closest('tr').data('key');
        request('remove', { key: key }).done(function (response) {
            if (response.success) {
                replaceList(response.data.html);
                var $undo = $('.taql-undo');
                $undo.prop('hidden', false).addClass('is-visible');
                window.setTimeout(function () { $undo.removeClass('is-visible').prop('hidden', true); }, 10000);
            }
        }).fail(function (response) { toast(messageFrom(response), 'is-error'); });
    });

    $(document).on('click', '.taql-undo button', function () {
        request('undo').done(function (response) {
            if (response.success) { replaceList(response.data.html); }
        }).fail(function (response) { toast(messageFrom(response), 'is-error'); });
    });

    $(document).on('click', '.taql-print', function () { window.print(); });

    $(document).on('click', '.taql-buy-now', function () {
        if (!window.confirm(TAQL.i18n.confirmBuy)) { return; }
        var $button = $(this).prop('disabled', true).addClass('loading');
        request('buy').done(function (response) {
            if (!response.success) { return; }
            replaceList(response.data.html);
            if (response.data.status === 'complete') {
                toast('Todos los productos fueron enviados al carrito. <a href="' + response.data.cartUrl + '">Ver carrito</a>', 'is-success');
            } else if (response.data.status === 'partial') {
                toast('Envío parcial. No pudieron agregarse: ' + response.data.failed.join(', '), 'is-warning');
            } else {
                toast('Ningún producto pudo agregarse al carrito.', 'is-error');
            }
            $(document.body).trigger('wc_fragment_refresh');
        }).fail(function (response) { toast(messageFrom(response), 'is-error'); })
          .always(function () { $button.prop('disabled', false).removeClass('loading'); });
    });

    $(document).on('submit', '.taql-form', function (event) {
        event.preventDefault();
        if (!window.confirm(TAQL.i18n.confirmSend)) { return; }
        var $form = $(this);
        var $button = $form.find('[type="submit"]').prop('disabled', true).addClass('loading');
        var data = {};
        $.each($form.serializeArray(), function (_, field) { data[field.name] = field.value; });
        request('submit', data).done(function (response) {
            if (response.success) {
                $form.replaceWith('<div class="taql-notice taql-success"><strong>' + response.data.message + '</strong></div>');
                window.scrollTo({ top: $('#taql-app').offset().top, behavior: 'smooth' });
            } else {
                toast(response.data.message || TAQL.i18n.error, 'is-error');
            }
        }).fail(function (response) { toast(messageFrom(response), 'is-error'); })
          .always(function () { $button.prop('disabled', false).removeClass('loading'); });
    });
})(jQuery);

