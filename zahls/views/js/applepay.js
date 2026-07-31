/**
 *    zahls Payment Gateway.
 *
 * @author    zahls <integration@support.ch>
 * @copyright 2026    zahls
 * @license   MIT License
 */
$(document).on('ready', function() {
    if ((window.ApplePaySession && ApplePaySession.canMakePayments()) !== true) {
        const $containerId = $('input[name="zahlsPaymentMethod"][value="apple-pay"]')
            .closest('.js-payment-option-form')
            .attr('id')
            ?.match(/\d+/)?.[0];

        if ($containerId) {
            $(`#payment-option-${$containerId}-container`).remove();
            console.warn('zahls.ch Apple Pay is not supported on this device/browser');
        }
    }
});
