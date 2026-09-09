(function () {
    'use strict';

    var S = window.tfpDashboardBilling || {};
    var initialized = false;

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    ready(function () {
        var form = document.querySelector('[data-tfp-billing-form]');
        var mount = document.querySelector('[data-tfp-stripe-card]');

        if (!form || !mount || initialized) {
            return;
        }

        var status = form.querySelector('[data-tfp-billing-status]');
        var submit = form.querySelector('[data-tfp-billing-submit]');

        function setStatus(message, state) {
            if (!status) return;
            status.textContent = message || '';
            if (state) {
                status.setAttribute('data-state', state);
            } else {
                status.removeAttribute('data-state');
            }
        }

        if (!S.publishableKey) {
            setStatus('Secure card setup is unavailable because the Stripe publishable key is missing.', 'error');
            return;
        }

        // Stripe.js normally loads before this file through the WordPress
        // dependency. A short retry also protects against optimisation plugins
        // that rewrite/defer external scripts and can otherwise leave Elements
        // mounted as an empty box.
        var attempts = 0;

        function initialiseStripe() {
            if (initialized) return;

            if (typeof window.Stripe !== 'function') {
                attempts++;
                if (attempts < 50) {
                    window.setTimeout(initialiseStripe, 100);
                    return;
                }
                setStatus('Secure card fields could not load. Please refresh and try again.', 'error');
                return;
            }

            var stripe;
            var elements;
            var card;

            try {
                stripe = window.Stripe(S.publishableKey);
                elements = stripe.elements();
                card = elements.create('card', {
                    style: {
                        base: {
                            color: '#151411',
                            fontFamily: 'Arial, sans-serif',
                            fontSize: '16px',
                            lineHeight: '24px',
                            '::placeholder': {
                                color: '#777777'
                            }
                        },
                        invalid: {
                            color: '#B3261E'
                        }
                    },
                    hidePostalCode: true
                });

                card.mount(mount);
                initialized = true;
            } catch (error) {
                setStatus(error && error.message ? error.message : 'Secure card fields could not be initialized.', 'error');
                return;
            }

            card.on('ready', function () {
                setStatus('');
            });

            card.on('change', function (event) {
                if (event && event.error) {
                    setStatus(event.error.message, 'error');
                } else {
                    setStatus('');
                }
            });

            function post(action, data) {
                var body = new FormData();
                body.append('action', action);
                body.append('nonce', S.nonce || '');

                Object.keys(data || {}).forEach(function (key) {
                    body.append(key, data[key]);
                });

                return fetch(S.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: body
                }).then(function (response) {
                    return response.text().then(function (text) {
                        var payload;
                        try {
                            payload = JSON.parse(text);
                        } catch (e) {
                            throw new Error('The server returned an invalid response. Please refresh and try again.');
                        }

                        if (!response.ok && (!payload || !payload.message)) {
                            throw new Error('Could not complete the payment request.');
                        }

                        return payload;
                    });
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (submit && submit.disabled) {
                    return;
                }

                if (submit) {
                    submit.disabled = true;
                }

                setStatus('Preparing secure card update...');

                post('tfp_stripe_create_setup_intent', {})
                    .then(function (response) {
                        if (!response || !response.success || !response.clientSecret) {
                            throw new Error((response && response.message) || 'Could not start secure card setup.');
                        }

                        return stripe.confirmCardSetup(response.clientSecret, {
                            payment_method: {
                                card: card
                            }
                        });
                    })
                    .then(function (result) {
                        if (result.error) {
                            throw new Error(result.error.message || 'Could not verify this card.');
                        }

                        if (!result.setupIntent || result.setupIntent.status !== 'succeeded') {
                            throw new Error('Card setup was not completed.');
                        }

                        setStatus('Saving payment method...');

                        return post('tfp_stripe_save_setup_intent', {
                            setup_intent_id: result.setupIntent.id
                        });
                    })
                    .then(function (response) {
                        if (!response || !response.success) {
                            throw new Error((response && response.message) || 'Could not save your payment method.');
                        }

                        var brand = document.querySelector('[data-tfp-card-brand]');
                        var expiry = document.querySelector('[data-tfp-card-expiry]');
                        var noCard = document.querySelector('[data-tfp-no-card]');

                        if (response.card) {
                            if (brand) {
                                brand.textContent = response.card.brand + ' ending in ' + response.card.last4;
                            }
                            if (expiry) {
                                expiry.textContent = response.card.expiry;
                            }
                            if (noCard) {
                                noCard.textContent = response.card.brand + ' ending in ' + response.card.last4;
                            }
                        }

                        card.clear();
                        setStatus(response.message || 'Payment method updated successfully.', 'success');
                    })
                    .catch(function (error) {
                        setStatus(
                            error && error.message
                                ? error.message
                                : 'A secure payment error occurred. Please try again.',
                            'error'
                        );
                    })
                    .finally(function () {
                        if (submit) {
                            submit.disabled = false;
                        }
                    });
            });
        }

        initialiseStripe();
    });
})();