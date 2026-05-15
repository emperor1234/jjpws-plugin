/* global jjpwsData */

(function () {
    'use strict';

    const state = {
        currentStep: 1,
        // Address
        street: '', city: '', state: '', zip: '',
        lat: null, lng: null,
        lotSqft: null, lotAcres: null, lotTier: null, lotLabel: null,
        distanceMiles: null,
        // Service
        serviceType: 'recurring',
        dogCount: 1,
        frequency: 'weekly',
        timeSinceCleaned: 'recent',
        annualPrepay: false,
        // Pricing
        breakdown: null,
        totalCents: null,
    };

    const $ = (id) => document.getElementById(id);
    const qS = (sel, ctx = document) => ctx.querySelector(sel);
    const qA = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    const fmt = (cents) => '$' + (cents / 100).toFixed(2);

    // ── Step navigation ────────────────────────────────────────────────────
    function goToStep(n) {
        qA('.jjpws-form-step').forEach(el => el.classList.toggle('jjpws-form-step--active', +el.dataset.step === n));
        qA('.jjpws-step').forEach(el => {
            const num = +el.dataset.step;
            el.classList.toggle('jjpws-step--active', num === n);
            el.classList.toggle('jjpws-step--completed', num < n);
        });
        state.currentStep = n;
        const wrap = $('jjpws-booking-wrap');
        if (wrap) window.scrollTo({ top: wrap.offsetTop - 20, behavior: 'smooth' });
    }

    function showQuoteForm(reason, prefill = {}) {
        document.getElementById('jjpws-booking-form').style.display = 'none';
        const wrap = document.getElementById('jjpws-quote-form');
        wrap.style.display = 'block';
        $('jjpws-quote-reason').value = reason || 'other';

        // Prefill if we have anything
        if (prefill.email && !$('jjpws-quote-email').value)  $('jjpws-quote-email').value  = prefill.email;
        const wrapTop = wrap.offsetTop - 20;
        window.scrollTo({ top: wrapTop, behavior: 'smooth' });
    }

    function hideQuoteForm() {
        document.getElementById('jjpws-booking-form').style.display = '';
        document.getElementById('jjpws-quote-form').style.display = 'none';
    }

    // ── AJAX ───────────────────────────────────────────────────────────────
    async function post(action, body) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', jjpwsData.nonce);
        for (const [k, v] of Object.entries(body)) {
            if (v !== null && v !== undefined && v !== '') fd.append(k, v);
        }
        const res = await fetch(jjpwsData.ajaxUrl, { method: 'POST', body: fd });
        return res.json();
    }

    // ── Lot size + distance lookup ─────────────────────────────────────────
    let lookupInProgress = false;

    async function lookupLotSize() {
        if (lookupInProgress) return;

        const street = $('jjpws-street')?.value.trim();
        const city   = $('jjpws-city')?.value.trim();
        const st     = $('jjpws-state')?.value.trim();
        const zip    = $('jjpws-zip')?.value.trim();

        if (!street || !city || !st || !/^\d{5}$/.test(zip)) return;

        lookupInProgress = true;
        showLotLoading(true);

        try {
            const json = await post('jjpws_lookup_lot_size', { street, city, state: st, zip });

            if (!json.success) {
                showLotManual();
                return;
            }

            const d = json.data;
            state.distanceMiles = d.distance_miles;
            // Always capture coords if returned, regardless of lot-detection outcome —
            // the server needs them to recompute distance authoritatively.
            if (d.lat) state.lat = d.lat;
            if (d.lng) state.lng = d.lng;
            if (d.lat) $('jjpws-lat').value = d.lat;
            if (d.lng) $('jjpws-lng').value = d.lng;
            $('jjpws-distance-miles').value = d.distance_miles ?? '';

            // Out of range
            if (d.out_of_range) {
                hideLotResolved();
                hideLotManual();
                showQuotePrompt(
                    'out_of_range',
                    `Your address is approximately ${d.distance_miles} miles from us, beyond our ${d.max_miles}-mile service radius.`
                );
                return;
            }

            hideQuotePrompt();

            // Lot size (auto or manual)
            if (d.source === 'manual_required' || !d.lot_size_category) {
                showLotManual();
                if (d.distance_miles !== null) showDistanceOnly(d);
            } else {
                state.lotSqft = d.lot_size_sqft;
                state.lotAcres = d.lot_size_acres;
                state.lotTier = d.lot_size_category;
                state.lotLabel = d.lot_size_label;
                if (d.lat) state.lat = d.lat;
                if (d.lng) state.lng = d.lng;
                setHiddenLot(d);
                showLotResolved(d);
                hideLotManual();

                // 1.5+ acres → quote
                if (d.requires_quote) {
                    showQuotePrompt('large_lot',
                        `Your lot is approximately ${d.lot_size_acres} acres. Lots over 1.5 acres need a custom quote.`);
                }
            }
        } catch (e) {
            console.error('JJPWS lot lookup error:', e);
            showLotManual();
        } finally {
            showLotLoading(false);
            lookupInProgress = false;
        }
    }

    function setHiddenLot(d) {
        $('jjpws-lot-category').value = d.lot_size_category || '';
        $('jjpws-lot-sqft').value     = d.lot_size_sqft || '';
        $('jjpws-lot-acres').value    = d.lot_size_acres || '';
        $('jjpws-distance-miles').value = d.distance_miles ?? '';
        if (d.lat) $('jjpws-lat').value = d.lat;
        if (d.lng) $('jjpws-lng').value = d.lng;
    }

    function showLotResolved(d) {
        $('jjpws-lot-resolved').style.display = 'block';
        const acresText = d.lot_size_acres
            ? `${d.lot_size_acres} acres (~${d.lot_size_sqft.toLocaleString()} sq ft) — ${d.lot_size_label}`
            : d.lot_size_label;
        $('jjpws-lot-acres-text').textContent = acresText;
        showDistanceOnly(d);
    }

    function showDistanceOnly(d) {
        if (d.distance_miles === null || d.distance_miles === undefined) {
            $('jjpws-distance-text').style.display = 'none';
            return;
        }
        $('jjpws-distance-text').style.display = 'block';
        $('jjpws-distance-value').textContent = `${d.distance_miles} miles from us`;
        const note = $('jjpws-distance-fee-note');
        if (d.distance_miles > d.free_miles) {
            const extra = d.distance_miles - d.free_miles;
            const fee = (extra * d.per_mile_cents / 100).toFixed(2);
            note.textContent = ` (travel fee: $${fee}/visit)`;
            note.style.display = 'inline';
        } else {
            note.style.display = 'none';
        }
    }

    function hideLotResolved() {
        $('jjpws-lot-resolved').style.display = 'none';
    }

    function showLotManual() {
        $('jjpws-lot-manual').style.display = 'block';
        hideLotResolved();
    }

    function hideLotManual() {
        $('jjpws-lot-manual').style.display = 'none';
    }

    function showLotLoading(show) {
        const el = $('jjpws-lot-loading');
        if (el) el.style.display = show ? 'flex' : 'none';
    }

    function showQuotePrompt(reason, message) {
        const wrap = $('jjpws-quote-prompt');
        wrap.style.display = 'block';
        $('jjpws-quote-prompt-msg').textContent = message;
        wrap.dataset.reason = reason;
    }

    function hideQuotePrompt() {
        $('jjpws-quote-prompt').style.display = 'none';
    }

    // ── Pricing ───────────────────────────────────────────────────────────
    let priceDebounce;

    function schedulePriceUpdate() {
        clearTimeout(priceDebounce);
        priceDebounce = setTimeout(fetchPrice, 300);
    }

    async function fetchPrice() {
        if (!state.lotTier || state.lotTier === 'large') return;

        const loading = $('jjpws-price-loading');
        const preview = $('jjpws-price-preview');
        const quote   = $('jjpws-step2-quote-prompt');

        loading.style.display = 'flex';
        preview.style.display = 'none';
        quote.style.display = 'none';

        try {
            const body = {
                service_type: state.serviceType,
                acreage_tier: state.lotTier,
                dog_count: state.dogCount,
                frequency: state.frequency,
                time_since_cleaned: state.timeSinceCleaned,
                lat: state.lat ?? '',
                lng: state.lng ?? '',
                distance_miles: state.distanceMiles ?? 0,
                annual_prepay: state.annualPrepay ? '1' : '0',
            };

            const json = await post('jjpws_calculate_price', body);

            if (!json.success) return;

            // Server may have recomputed distance more accurately
            if (typeof json.data.distance_miles === 'number') {
                state.distanceMiles = json.data.distance_miles;
            }

            if (json.data.requires_quote) {
                state.breakdown = null;
                state.totalCents = null;
                $('jjpws-step2-quote-msg').textContent = json.data.reason === 'too_many_dogs'
                    ? 'For yards with 5 or more dogs, we provide a custom quote.'
                    : 'Your property requires a custom quote.';
                quote.style.display = 'block';
                return;
            }

            state.breakdown = json.data.breakdown;
            state.totalCents = json.data.breakdown.total_cents;
            $('jjpws-total-cents').value = state.totalCents;

            renderPricePreview(json.data.breakdown);

        } catch (e) {
            console.error(e);
        } finally {
            loading.style.display = 'none';
        }
    }

    function renderPricePreview(b) {
        const preview = $('jjpws-price-preview');
        const label = $('jjpws-price-label');
        const amount = $('jjpws-price-amount');
        const breakdown = $('jjpws-price-breakdown');

        if (b.service_type === 'one_time') {
            label.textContent = 'One-Time Cleanup Total:';
            amount.textContent = fmt(b.total_cents);
            breakdown.innerHTML = renderBreakdownRows([
                ['Base price', b.one_time_base_cents],
                ['Acreage premium', b.acreage_premium_cents],
                ['Travel fee', b.distance_fee_cents],
                ['Neglect surcharge', b.neglect_surcharge_cents],
            ]);
        } else if (b.annual_prepay) {
            label.textContent = 'Annual Prepay (10% off):';
            amount.textContent = fmt(b.annual_total_cents);
            breakdown.innerHTML = renderBreakdownRows([
                ['Per-visit total', b.per_visit_total_cents],
                ['Visits per month', b.visits_per_month, true],
                ['Monthly total', b.monthly_cents],
                ['Annual savings', -b.annual_savings_cents],
                ['Neglect surcharge', b.neglect_surcharge_cents],
            ]);
        } else {
            label.textContent = 'Monthly Cost:';
            amount.textContent = fmt(b.monthly_cents);
            const rows = [
                ['Per visit (base)', b.base_per_visit_cents + (b.acreage_premium_cents || 0)],
                ['Visits per month', b.visits_per_month, true],
            ];
            if (b.distance_fee_monthly > 0) {
                rows.push([`Travel fee (${(state.distanceMiles ?? 0).toFixed(1)} mi)`, b.distance_fee_monthly]);
            }
            if (b.neglect_surcharge_cents > 0) {
                rows.push(['First-month neglect surcharge', b.neglect_surcharge_cents]);
            }
            breakdown.innerHTML = renderBreakdownRows(rows);
        }

        preview.style.display = 'block';
    }

    function renderBreakdownRows(rows) {
        return rows
            .filter(([_, val]) => val !== 0 && val !== null && val !== undefined)
            .map(([label, val, isCount]) => `
                <div class="jjpws-bd-row">
                    <span>${label}</span>
                    <span>${isCount ? val : fmt(val)}</span>
                </div>
            `).join('');
    }

    // ── Validation ────────────────────────────────────────────────────────
    function clearErrors() {
        qA('.jjpws-field-error').forEach(el => el.textContent = '');
        qA('.jjpws-error').forEach(el => el.classList.remove('jjpws-error'));
    }

    function markError(inputId, errId, msg) {
        const inp = $(inputId);
        const err = $(errId);
        if (inp) inp.classList.add('jjpws-error');
        if (err) err.textContent = msg;
        return false;
    }

    function validateStep1() {
        clearErrors();
        let valid = true;

        const street = $('jjpws-street')?.value.trim();
        const city   = $('jjpws-city')?.value.trim();
        const st     = $('jjpws-state')?.value.trim();
        const zip    = $('jjpws-zip')?.value.trim();

        if (!street || street.length < 5) valid = markError('jjpws-street', 'err-street', 'Please enter a valid street address.');
        if (!city) { markError('jjpws-city', 'err-city', 'City is required.'); valid = false; }
        if (!st)   { valid = false; }
        if (!/^\d{5}$/.test(zip)) valid = false;

        // Lot tier required
        const lotCat = $('jjpws-lot-category')?.value;
        const manualVisible = $('jjpws-lot-manual')?.style.display !== 'none';

        if (manualVisible) {
            const sel = $('jjpws-lot-manual-select')?.value;
            if (!sel) {
                markError('jjpws-lot-manual-select', 'err-lot-manual', 'Please select your lot size.');
                valid = false;
            } else {
                state.lotTier = sel;
                $('jjpws-lot-category').value = sel;
            }
        } else if (!lotCat) {
            valid = false;
        } else {
            state.lotTier = lotCat;
        }

        if (state.lotTier === 'large') {
            return false; // quote prompt shown already
        }

        return valid;
    }

    function collectStep1() {
        state.street = $('jjpws-street')?.value.trim();
        state.city   = $('jjpws-city')?.value.trim();
        state.state  = $('jjpws-state')?.value.trim();
        state.zip    = $('jjpws-zip')?.value.trim();
    }

    // ── Review ────────────────────────────────────────────────────────────
    function populateReview() {
        const freqLabels = { twice_weekly: 'Twice a Week', weekly: 'Weekly', biweekly: 'Bi-Weekly' };
        const timeLabels = {
            recent: 'Less than 4 weeks ago',
            mid:    '4–7 weeks ago',
            long:   '8+ weeks ago / new yard',
        };
        const acreLabels = { small: 'Under 0.75 acre', medium: '0.75–1.5 acres' };

        const isOneTime = state.serviceType === 'one_time';

        $('review-type').textContent = isOneTime
            ? 'One-Time Cleanup'
            : (state.annualPrepay ? 'Recurring (Annual Prepay)' : 'Recurring (Monthly)');

        $('review-address').textContent = [state.street, state.city, state.state, state.zip].filter(Boolean).join(', ');
        $('review-lot').textContent = `${acreLabels[state.lotTier] || state.lotTier}` +
            (state.lotAcres ? ` (${state.lotAcres} acres)` : '');

        $('review-dogs-row').style.display = isOneTime ? 'none' : 'flex';
        $('review-frequency-row').style.display = isOneTime ? 'none' : 'flex';
        $('review-dogs').textContent = state.dogCount;
        $('review-frequency').textContent = freqLabels[state.frequency] || state.frequency;
        $('review-time-since').textContent = timeLabels[state.timeSinceCleaned] || state.timeSinceCleaned;

        $('review-price').textContent = fmt(state.totalCents);

        if (!isOneTime && !state.annualPrepay && state.breakdown?.recurring_monthly_cents) {
            $('review-recurring-row').style.display = 'flex';
            $('review-recurring').textContent = fmt(state.breakdown.recurring_monthly_cents) + '/mo';
            $('review-total-label').textContent = 'First payment';
        } else {
            $('review-recurring-row').style.display = 'none';
            $('review-total-label').textContent = isOneTime ? 'Total' : (state.annualPrepay ? 'Annual Total' : 'First payment');
        }
    }

    // ── Checkout ──────────────────────────────────────────────────────────
    async function handleCompleteBooking() {
        const btn     = $('jjpws-complete-booking');
        const errDiv  = $('jjpws-checkout-error');
        const spinner = btn?.querySelector('.jjpws-btn__spinner');

        errDiv.style.display = 'none';

        if (!jjpwsData.isLoggedIn) {
            saveFormState();
            window.location.href = jjpwsData.loginUrl;
            return;
        }

        btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const body = {
                service_type: state.serviceType,
                street: state.street, city: state.city, state: state.state, zip: state.zip,
                lat: state.lat, lng: state.lng,
                lot_size_sqft: state.lotSqft,
                lot_size_acres: state.lotAcres,
                acreage_tier: state.lotTier,
                dog_count: state.dogCount,
                frequency: state.frequency,
                time_since_cleaned: state.timeSinceCleaned,
                annual_prepay: state.annualPrepay ? '1' : '0',
                distance_miles: state.distanceMiles ?? 0,
                total_price_cents: state.totalCents,
            };

            const json = await post('jjpws_create_checkout_session', body);

            if (json.success && json.data.checkout_url) {
                window.location.href = json.data.checkout_url;
            } else {
                errDiv.textContent = json.data?.message || 'Something went wrong. Please try again.';
                errDiv.style.display = 'block';
                btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            }
        } catch (e) {
            errDiv.textContent = 'Network error. Please check your connection and try again.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            if (spinner) spinner.style.display = 'none';
        }
    }

    // ── Quote form ────────────────────────────────────────────────────────
    async function handleQuoteSubmit() {
        const btn     = $('jjpws-quote-submit');
        const spinner = btn?.querySelector('.jjpws-btn__spinner');
        const errDiv  = $('jjpws-quote-error');
        const success = $('jjpws-quote-success');
        const actions = $('jjpws-quote-actions');

        errDiv.style.display = 'none';
        clearErrors();

        const email = $('jjpws-quote-email').value.trim();
        const message = $('jjpws-quote-message').value.trim();

        let valid = true;
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
            markError('jjpws-quote-email', 'err-quote-email', 'Please enter a valid email.');
            valid = false;
        }
        if (!message) {
            markError('jjpws-quote-message', 'err-quote-message', 'Please write a short message.');
            valid = false;
        }
        if (!valid) return;

        btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const body = {
                name: $('jjpws-quote-name').value.trim(),
                email,
                phone: $('jjpws-quote-phone').value.trim(),
                message,
                reason: $('jjpws-quote-reason').value || 'other',
                street: state.street, city: state.city, state: state.state, zip: state.zip,
                lot_size_acres: state.lotAcres ?? '',
                dog_count: state.dogCount,
                distance_miles: state.distanceMiles ?? '',
            };

            const json = await post('jjpws_submit_quote', body);

            if (json.success) {
                success.style.display = 'block';
                actions.style.display = 'none';
                qA('#jjpws-quote-form .jjpws-field').forEach(el => el.style.display = 'none');
            } else {
                errDiv.textContent = json.data?.message || 'Something went wrong.';
                errDiv.style.display = 'block';
                btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            }
        } catch (e) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            if (spinner) spinner.style.display = 'none';
        }
    }

    // ── Form state persistence ────────────────────────────────────────────
    const STORAGE_KEY = 'jjpws_form_state_v2';

    function saveFormState() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (_) {}
    }

    function restoreFormState() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const saved = JSON.parse(raw);

            Object.assign(state, saved);

            // Hydrate inputs
            $('jjpws-street').value = state.street || '';
            $('jjpws-city').value   = state.city   || '';
            $('jjpws-state').value  = state.state  || '';
            $('jjpws-zip').value    = state.zip    || '';
            if (state.lat)      $('jjpws-lat').value         = state.lat;
            if (state.lng)      $('jjpws-lng').value          = state.lng;
            if (state.lotSqft)  $('jjpws-lot-sqft').value     = state.lotSqft;
            if (state.lotAcres) $('jjpws-lot-acres').value    = state.lotAcres;
            if (state.lotTier)  $('jjpws-lot-category').value = state.lotTier;
            if (state.distanceMiles !== null) $('jjpws-distance-miles').value = state.distanceMiles;

            $('jjpws-dog-count').value = state.dogCount;
            qA('input[name="frequency"]').forEach(r => r.checked = (r.value === state.frequency));
            qA('input[name="service_type"]').forEach(r => r.checked = (r.value === state.serviceType));
            $('jjpws-time-since').value = state.timeSinceCleaned;
            $('jjpws-annual-prepay').checked = !!state.annualPrepay;

            if (state.totalCents) $('jjpws-total-cents').value = state.totalCents;

            sessionStorage.removeItem(STORAGE_KEY);

            if (state.lotLabel) {
                $('jjpws-lot-resolved').style.display = 'block';
                $('jjpws-lot-acres-text').textContent =
                    state.lotAcres ? `${state.lotAcres} acres — ${state.lotLabel}` : state.lotLabel;
            }

            applyServiceTypeToggle();
            goToStep(3);
            populateReview();

            // User just came back from login/registration — swap the auth gate for the checkout button
            if (jjpwsData.isLoggedIn) {
                const gate    = $('jjpws-auth-gate');
                const actions = $('jjpws-checkout-actions');
                if (gate)    gate.style.display    = 'none';
                if (actions) actions.style.display = '';
            }
        } catch (e) { console.error(e); }
    }

    // ── UI toggles ────────────────────────────────────────────────────────
    function applyServiceTypeToggle() {
        const isOneTime = state.serviceType === 'one_time';
        $('jjpws-recurring-fields').style.display = isOneTime ? 'none' : 'block';
        $('jjpws-annual-prepay-row').style.display = isOneTime ? 'none' : 'block';
    }

    function initStepper() {
        qS('.jjpws-stepper__btn--minus')?.addEventListener('click', () => {
            const inp = $('jjpws-dog-count');
            const v = Math.max(1, parseInt(inp.value) - 1);
            inp.value = v;
            state.dogCount = v;
            schedulePriceUpdate();
        });
        qS('.jjpws-stepper__btn--plus')?.addEventListener('click', () => {
            const inp = $('jjpws-dog-count');
            const v = Math.min(10, parseInt(inp.value) + 1);
            inp.value = v;
            state.dogCount = v;
            schedulePriceUpdate();
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────
    function init() {
        // Step 1
        $('jjpws-step1-next')?.addEventListener('click', async () => {
            // Fire lookup first if the address is complete but lot tier hasn't
            // been resolved yet and the manual selector isn't already showing.
            // This covers the case where the user typed an address without
            // triggering a field blur (e.g. pasted the address, clicked Continue).
            const alreadyResolved = !!$('jjpws-lot-category')?.value;
            const manualShowing   = $('jjpws-lot-manual')?.style.display !== 'none';
            if (!alreadyResolved && !manualShowing) {
                await lookupLotSize();
            }
            if (!validateStep1()) return;
            collectStep1();
            goToStep(2);
            fetchPrice();
        });

        $('jjpws-step2-back')?.addEventListener('click', () => goToStep(1));
        $('jjpws-step2-next')?.addEventListener('click', () => {
            if (!state.totalCents) return;
            populateReview();
            goToStep(3);
        });
        $('jjpws-step3-back')?.addEventListener('click', () => goToStep(2));

        $('jjpws-complete-booking')?.addEventListener('click', handleCompleteBooking);

        // Service type
        qA('input[name="service_type"]').forEach(r => {
            r.addEventListener('change', () => {
                state.serviceType = r.value;
                applyServiceTypeToggle();
                schedulePriceUpdate();
            });
        });

        // Frequency
        qA('input[name="frequency"]').forEach(r => {
            r.addEventListener('change', () => {
                state.frequency = r.value;
                schedulePriceUpdate();
            });
        });

        // Time since cleaned
        $('jjpws-time-since')?.addEventListener('change', (e) => {
            state.timeSinceCleaned = e.target.value;
            schedulePriceUpdate();
        });

        // Annual prepay
        $('jjpws-annual-prepay')?.addEventListener('change', (e) => {
            state.annualPrepay = e.target.checked;
            schedulePriceUpdate();
        });

        // Manual lot select
        $('jjpws-lot-manual-select')?.addEventListener('change', (e) => {
            const v = e.target.value;
            state.lotTier = v;
            $('jjpws-lot-category').value = v;
            if (v === 'large') {
                showQuotePrompt('large_lot', 'Lots over 1.5 acres need a custom quote.');
            } else {
                hideQuotePrompt();
            }
        });

        // Address change → re-lookup (any field blur, when all four are filled)
        ['jjpws-street', 'jjpws-city', 'jjpws-state', 'jjpws-zip'].forEach(id => {
            $(id)?.addEventListener('change', lookupLotSize);
        });
        document.addEventListener('jjpws:addressSelected', lookupLotSize);

        // Quote form
        $('jjpws-show-quote-form')?.addEventListener('click', () => {
            const reason = $('jjpws-quote-prompt')?.dataset.reason || 'other';
            showQuoteForm(reason, { email: jjpwsData.userEmail });
        });
        $('jjpws-show-quote-form-2')?.addEventListener('click', () => {
            showQuoteForm('too_many_dogs', { email: jjpwsData.userEmail });
        });
        $('jjpws-quote-cancel')?.addEventListener('click', hideQuoteForm);
        $('jjpws-quote-submit')?.addEventListener('click', handleQuoteSubmit);

        initStepper();

        // Save state before auth redirects so the form survives login/registration
        ['jjpws-register-link', 'jjpws-login-link'].forEach(id => {
            $(id)?.addEventListener('click', () => saveFormState());
        });

        // Resume after auth
        if (jjpwsData.resumeFlow) restoreFormState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
