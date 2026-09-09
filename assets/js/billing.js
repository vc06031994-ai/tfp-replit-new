(function () {
    'use strict';

    var S = window.tfpDashboardBilling || {};
    if (!S.publishableKey || typeof window.Stripe !== 'function') {
        return;
    }

    var form = document.querySelector('[data-tfp-billing-form]');
    var mount = document.querySelector('[data-tfp-stripe-card]');
    if (!form || !mount) {
        return;
    }

    var stripe = window.Stripe(S.publishableKey);
    var elements = stripe.elements();
    var card = elements.create('card', {
        style: {
            base: {
                color: '#151411',
                fontFamily: 'Eudoxus Sans, Arial, sans-serif',
                fontSize: '16px',
                '::placeholder': { color: '#777' }
            },
            invalid: { color: '#B3261E' }
        }
    });
    card.mount(mount);

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

    function post(action, data) {
        var body = new FormData();
        body.append('action', action);
        body.append('nonce', S.nonce);
        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });

        return fetch(S.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).then(function (response) {
            return response.json();
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submit && submit.disabled) return;

        if (submit) submit.disabled = true;
        setStatus('Preparing secure card update...');

        post('tfp_stripe_create_setup_intent', {})
            .then(function (response) {
                if (!response || !response.success || !response.clientSecret) {
                    throw new Error((response && response.message) || 'Could not start secure card setup.');
                }

                return stripe.confirmCardSetup(response.clientSecret, {
                    payment_method: { card: card }
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
                setStatus(error && error.message ? error.message : 'A secure payment error occurred. Please try again.', 'error');
            })
            .finally(function () {
                if (submit) submit.disabled = false;
            });
    });
})();