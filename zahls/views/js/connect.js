/**
 *    zahls Payment Gateway.
 *
 * @author    zahls <integration@support.ch>
 * @copyright 2025    zahls
 * @license   MIT License
 */
function connect() {
    const button = $('#connect-with-platform-button');
    const platformUrl = $('#zahls-platform-select').val();

    let popupWindow = createPopup(platformUrl);

    // Check if the popup is closed manually (i.e., without receiving a message)
    let popupCheck;
    popupCheck = setInterval(() => {
        if (popupWindow.closed) {
            popupWindow = null;
            clearInterval(popupCheck);
            button.prop('disabled', false);
        }
    }, 500)

    button.prop('disabled', true);
}

// Receive postMessage from popup
window.addEventListener('message', function (event) {
    if (!event.data || !event.data.instance) {
        return;
    }
    const button = $('#connect-with-platform-button');
    button.prop('disabled', false);

    const apiKey = event.data.instance.apikey;
    const instance = event.data.instance.name;

    $('#zahls-instance-name-input').val(instance);
    $('#zahls-api-secret-input').val(apiKey);

    $('#save-settings-button').click();

});

const createPopup = (platformUrl) => {
    const popupWidth = 900;
    const popupHeight = 900;

    // Get the parent window's size and position
    const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
    const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;

    const width = window.innerWidth
        ? window.innerWidth
        : document.documentElement.clientWidth
            ? document.documentElement.clientWidth
            : screen.width;

    const height = window.innerHeight
        ? window.innerHeight
        : document.documentElement.clientHeight
            ? document.documentElement.clientHeight
            : screen.height;

    const left = dualScreenLeft + (width - popupWidth) / 2;
    const top = dualScreenTop + (height - popupHeight) / 2;

    const params = `width=${popupWidth},height=${popupHeight},top=${top},left=${left},resizable=no,scrollbars=yes`
    const url = `https://login.${platformUrl}?action=connect&plugin_id=2`;
    return window.open(url, 'Connect    zahls', params);
}
