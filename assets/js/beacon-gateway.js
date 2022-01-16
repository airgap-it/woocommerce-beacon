/**
 * Initiate configuration parameters
 */
const API_BASE = php_params['api_base'];
const RECIPIENT = php_params['recipient'];
const DECIMALS = php_params['decimals'];
const CONTRACT = php_params['contract'];
const AMOUNT = (php_params['amount'] * 10 ** DECIMALS).toString();
const TOKEN_ID = php_params['token_id'].toString();
const STORE_NAME = php_params['store_name'];
const CURRENCY_SYMBOL = php_params['currency_symbol'];
const PATH = php_params['path'];
const REQUIRED_CONFIRMATIONS = 5;
const POLLING_INTERVAL = 1 * 1000;

/**
 * Setup state
 */
var previousType = null;
var paymentInitiated = false;
const client = new beacon.DAppClient({
    name: STORE_NAME,
});

/**
 * Prepare operation
 */
var OPERATION = {
    kind: 'transaction',
    destination: RECIPIENT,
    amount: AMOUNT,
};

// if a contract is set, invoke contract instead of transfer
if (CONTRACT) {
    OPERATION = {
        kind: beacon.TezosOperationType.TRANSACTION,
        amount: "0",
        destination: CONTRACT,
        parameters: {
            entrypoint: "transfer",
            value: [{
                prim: "Pair",
                args: [{
                        string: '', // account.address,
                    },
                    [{
                        prim: "Pair",
                        args: [{
                                string: RECIPIENT,
                            },
                            {
                                prim: "Pair",
                                args: [{
                                        int: TOKEN_ID,
                                    },
                                    {
                                        int: AMOUNT,
                                    },
                                ],
                            },
                        ],
                    }, ],
                ],
            }, ],
        },
    };
}

console.log(OPERATION)

/**
 * Displays a message on the store checkout website
 * @param  {String}  heading    Title of the message
 * @param  {String}  text       Content of the message
 * @param  {String}  type       Type to controll image and background color
 * @param  {Boolean} hide       Flag if message should be hidden
 */
const showMessage = function(heading, text, type, hide = false) {
    const element = document.getElementById('beacon-status');

    if (hide) {
        element.style.display = "none";
    } else {
        element.style.display = "block";
    }

    if (previousType != type) {
        previousType = type;
        document.getElementById('beacon-img').src = PATH + '/wp-content/plugins/beacon-gateway/assets/svg/' + type + '.svg';
        switch (type) {
            case 'error':
                element.style.backgroundColor = "#A94442";
                break;
            case 'info':
                element.style.backgroundColor = "white";
                break;
            case 'warning':
                element.style.backgroundColor = "orange";
                break;
            case 'progress':
                element.style.backgroundColor = "white";
                break;
            default:
                element.style.backgroundColor = "none";
        }
    }
    document.getElementById('beacon-heading').innerHTML = heading;
    document.getElementById('beacon-text').innerHTML = text;
};

/**
 * GET request helper that returns an object or null
 * @param  {String}             url         Url to GET
 * @param  {Function(object)}   callback    Callback function that gets called at the end of the request
 */
const getHTTP = function(url, callback) {
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.open("GET", url, true);
    xmlHttp.onload = function(e) {
        if (xmlHttp.readyState === 4) {
            if (xmlHttp.status === 200) {
                callback(JSON.parse(xmlHttp.responseText))
            } else {
                callback(null)
            }
        }
    };
    xmlHttp.onerror = function(e) {
        callback(null)
    };
    xmlHttp.send(null);
};

/**
 * Validates if the checkout form is valid
 * @return {Boolean}    true if all fields contain a valid value
 */
const validateForm = function() {
    var isValid = true;
    [
        'billing_first_name',
        'billing_last_name',
        'billing_country',
        'billing_address_1',
        'billing_postcode',
        'billing_city',
    ].forEach(id => {
        if (document.getElementById(id).value + '' == '') {
            isValid &= false;
        }
    })
    var isEmail = /\S+@\S+\.\S+/;
    isValid &= isEmail.test(document.getElementById('billing_email').value);
    return isValid;
}

/**
 * Invoke beacon signing request
 * @param  {object}  account    Beacon account object
 */
const signOperation = () => {
    client
        .requestOperation({
            operationDetails: [OPERATION],
        })
        .then((response) => {
            showMessage('Payment process', 'Transaction hash not (yet) found', 'progress', false);
            // Hide payment button
            document.getElementById('beacon-connect').style.display = 'none';
            // Fill hidden transaction field
            document.getElementById("beacon_transactionHash").value = response.transactionHash;
            // Continue form
            document.getElementById("place_order").click()
        })
        .catch(() => {
            showMessage('Payment cancelled', 'Please reload the page', 'warning', false);
        });
};

/**
 * Watch the form for changes
 */
const startBeacon = function(event) {
    // stop default behaviour
    event.stopPropagation();
    event.preventDefault();

    // Update UI
    paymentInitiated = true;
    showMessage('Payment initiated', 'Check beacon for the next steps', 'info', false);
    // Invoke beacon
    client.getActiveAccount().then((activeAccount) => {
        if (activeAccount) {
            // Premission has been gratend, initiate operation
            signOperation();
        } else {
            // Permission missing, we need to request permissions first
            client.requestPermissions().then((permissions) => {
                // Initiate operation
                signOperation();
            }).catch(() => {
                showMessage('Payment process', 'Beacon request aborted, please reload the website', 'error', false);
            });
        }
    });
    return true;
};

/**
 * Watch the form for changes
 */
setInterval(function() {
    if (validateForm()) {
        const transactionHash = document.getElementById("beacon_transactionHash").value;
        if (transactionHash) {
            // Request current blockchain information
            getHTTP(API_BASE + 'operations/' + transactionHash, function(responseOperations) {
                if (responseOperations) {
                    getHTTP(API_BASE + 'head', function(responseHead) {
                        var confirmations = responseHead["level"] - responseOperations[0]["level"];
                        // Check if transaction is confirmed
                        if (confirmations >= REQUIRED_CONFIRMATIONS) {
                            // Enough confirmations found, post to server for serverside validation
                            showMessage('Payment in process', 'Enough confirmations...', 'success', false);
                            document.getElementById('beacon-connect').click();
                        } else {
                            // Await more confirmations...
                            showMessage('Payment in process', confirmations + ' out of ' + REQUIRED_CONFIRMATIONS + ' confirmations', 'progress', false);
                            document.getElementById('beacon-connect').style.display = 'none';
                        }
                    });
                } else {
                    // Hash found, but not yet known/broadcastet by the network
                    showMessage('Payment process', 'Transaction hash not (yet) found', 'progress', false);
                }
            });
        } else if (!paymentInitiated) {
            // Form valid, ready for payment
            showMessage('Payment process', 'Ready for payment (click button below)', 'info', false);
        }
    } else {
        // Form invalid, wait for more information
        showMessage('Payment process', 'Missing contact information', 'info', false);
    }
}, POLLING_INTERVAL);