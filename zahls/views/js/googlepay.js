/**
 *    zahls Payment Gateway.
 *
 * @author    zahls <integration@support.ch>
 * @copyright 2026    zahls
 * @license   MIT License
 */
$(document).on('ready', function() {
    try {
        const baseRequest = {
            apiVersion: 2,
            apiVersionMinor: 0
        };
        const allowedCardNetworks = ['MASTERCARD', 'VISA'];
        const allowedCardAuthMethods = ['CRYPTOGRAM_3DS'];
        const baseCardPaymentMethod = {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: allowedCardAuthMethods,
                allowedCardNetworks: allowedCardNetworks
            }
        };

        const isReadyToPayRequest = Object.assign({}, baseRequest);
        isReadyToPayRequest.allowedPaymentMethods = [
            baseCardPaymentMethod
        ];
        const paymentsClient = new google.payments.api.PaymentsClient(
            {
                environment: 'TEST'
            }
        );
        paymentsClient.isReadyToPay(isReadyToPayRequest).then(function(response) {
            if (!response.result) {
                const $containerId = $('input[name="zahlsPaymentMethod"][value="google-pay"]')
                .closest('.js-payment-option-form')
                .attr('id')
                ?.match(/\d+/)?.[0];

                if ($containerId) {
                    $(`#payment-option-${$containerId}-container`).remove();
                    console.warn('zahls.ch Google Pay is not supported on this device/browser');
                }
            }
        }).catch(function(err) {
            console.log(err);
        });
    } catch (err) {
        console.log(err);
    }
});
