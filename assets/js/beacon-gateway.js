/**
 * Initiate configuration parameters
 */
const API_BASE = php_params['api_base'];
const RECIPIENT = php_params['recipient'];
const AMOUNT = php_params['amount'];
const STORE_NAME = php_params['store_name'];
const PATH = php_params['path'];
const REQUIRED_CONFIRMATIONS = parseInt(php_params['confirmations']);
const POLLING_INTERVAL = 5 * 1000;

/**
 * Setup state
 */
var previousType = null;
var paymentInitiated = false;
const client = new beacon.DAppClient({
    name: STORE_NAME,
});

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
        document.getElementById('beacon-img').src = PATH + '/assets/svg/' + type + '.svg';
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
 * Prepare operation
 */
const prepareOperation = () => {
    const currency = document.getElementById('beacon-select').value.split('-');
    const rate = currency[1];
    const decimals = currency[2];
    const contract = currency[3];
    const token_id = currency[4];
    const calculated_amount = (rate * AMOUNT * 10 ** decimals).toString();

    if (contract === ' ') {
        return {
            kind: 'transaction',
            destination: RECIPIENT,
            amount: calculated_amount,
        };
    }
    console.log('contract', contract);
    console.log('calculated_amount', calculated_amount);
    console.log('token_id', token_id);
    console.log('decimals', decimals);
    return {
        kind: beacon.TezosOperationType.TRANSACTION,
        amount: "0",
        destination: contract,
        parameters: {
            entrypoint: "transfer",
            value: [{
                prim: "Pair",
                args: [{
                        string: contract,
                    },
                    [{
                        prim: "Pair",
                        args: [{
                                string: RECIPIENT,
                            },
                            {
                                prim: "Pair",
                                args: [{
                                        int: token_id,
                                    },
                                    {
                                        int: calculated_amount,
                                    },
                                ],
                            },
                        ],
                    }, ],
                ],
            }, ],
        },
    };
};

/**
 * Invoke beacon signing request
 * @param  {object}  account    Beacon account object
 */
const signOperation = () => {
    client
        .requestOperation({
            operationDetails: [prepareOperation()],
        })
        .then((response) => {
            showMessage('Payment process', 'Transaction hash not (yet) found', 'progress', false);
            // Hide payment button
            document.getElementById('beacon-connect').style.display = 'none';
            // Fill hidden transaction field
            document.getElementById("beacon_transactionHash").value = response.transactionHash;
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
    // if (validateForm()) {
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
                        // document.getElementById('beacon-connect').click();
                        document.getElementById("place_order").click()

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
}, POLLING_INTERVAL);