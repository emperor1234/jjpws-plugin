/* global jjpwsData */

(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────
    const state = {
        currentStep: 1,
        street: '',
        city: '',
        state: '',
        zip: '',
        lat: null,
        lng: null,
        lotSqft: null,
        lotCategory: null,
        lotLabel: null,
        dogCount: 1,
        frequency: 'weekly',
        priceCents: null,
        priceFormatted: null,
    };

    // ── DOM helpers ────────────────────────────────────────────────────────
    const $  = (id) => document.getElementById(id);
    const qS = (sel, ctx = document) => ctx.querySelector(sel);
    const qA = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    // ── Step navigation ────────────────────────────────────────────────────
    function goToStep(n) {
        qA('.jjpws-form-step').forEach(el => {
            el.classList.toggle('jjpws-form-step--active', +el.dataset.step === n);
        });
        qA('.jjpws-step').forEach(el => {
            const num = +el.dataset.step;
            el.classList.toggle('jjpws-step--active',    num === n);
            el.classList.toggle('jjpws-step--completed', num < n);
        });
        state.currentStep = n;
        window.scrollTo({ top: document.getElementById('jjpws-booking-wrap')?.offsetTop - 20 ?? 0, behavior: 'smooth' });
    }

    // ── Field helpers ──────────────────────────────────────────────────────
    function fieldError(id, msg) {
        const el = $(id);
        if (el) { el.textContent = msg; }
    }

    function clearErrors() {
        qA('.jjpws-field-error').forEach(el => (el.textContent = ''));
        qA('.jjpws-error').forEach(el => el.classList.remove('jjpws-error'));
    }

    function markError(inputId, errId, msg) {
        const inp = $(inputId);
        const err = $(errId);
        if (inp) inp.classList.add('jjpws-error');
        if (err) err.textContent = msg;
        return false;
    }

    // ── AJAX helper ────────────────────────────────────────────────────────
    async function post(action, body) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', jjpwsData.nonce);
        for (const [k, v] of Object.entries(body)) {
            if (v !== null && v !== undefined) fd.append(k, v);
        }
        const res  = await fetch(jjpwsData.ajaxUrl, { method: 'POST', body: fd });
        return res.json();
    }

    // ── Lot size lookup ────────────────────────────────────────────────────
    async function lookupLotSize() {
        const street = $('jjpws-street')?.value.trim();
        const city   = $('jjpws-city')?.value.trim();
        const st     = $('jjpws-state')?.value.trim();
        const zip    = $('jjpws-zip')?.value.trim();

        if (!street || !city || !st || !/^\d{5}$/.test(zip)) return;

        showLotLoading(true);

        try {
            const json = await post('jjpws_lookup_lot_size', { street, city, state: st, zip });

            if (json.success && json.data.source !== 'manual_required') {
                const d = json.data;
                state.lotSqft     = d.lot_size_sqft;
                state.lotCategory = d.lot_size_category;
                state.lotLabel    = d.lot_size_label;
                if (d.lat) state.lat = d.lat;
                if (d.lng) state.lng = d.lng;
                setHiddenLot(d.lot_size_category, d.lot_size_sqft);
                showLotResolved(d.lot_size_label);
                hideLotManual();
            } else {
                showLotManual();
            }
        } catch {
            showLotManual();
        } finally {
            showLotLoading(false);
        }
    }

    function setHiddenLot(category, sqft) {
        $('jjpws-lot-category').value = category || '';
        $('jjpws-lot-sqft').value     = sqft     || '';
    }

    function showLotResolved(label) {
        const wrap = $('jjpws-lot-resolved');
        const txt  = $('jjpws-lot-label-text');
        if (wrap) wrap.style.display = 'block';
        if (txt)  txt.textContent    = label || '';
        hideLotManual();
    }

    function showLotManual() {
        const wrap = $('jjpws-lot-manual');
        if (wrap) wrap.style.display = 'block';
        const resolved = $('jjpws-lot-resolved');
        if (resolved) resolved.style.display = 'none';
    }

    function hideLotManual() {
        const wrap = $('jjpws-lot-manual');
        if (wrap) wrap.style.display = 'none';
    }

    function showLotLoading(show) {
        const el = $('jjpws-lot-loading');
        if (el) el.style.display = show ? 'flex' : 'none';
    }

    // ── Price calculation ──────────────────────────────────────────────────
    let priceDebounce;

    function schedulePriceUpdate() {
        clearTimeout(priceDebounce);
        priceDebounce = setTimeout(fetchPrice, 300);
    }

    async function fetchPrice() {
        const category = state.lotCategory;
        const dogs     = state.dogCount;
        const freq     = state.frequency;

        if (!category || !dogs || !freq) return;

        const loading = $('jjpws-price-loading');
        const preview = $('jjpws-price-preview');
        if (loading) loading.style.display = 'flex';
        if (preview) preview.style.display = 'none';

        try {
            const json = await post('jjpws_calculate_price', {
                lot_size_category: category,
                dog_count:         dogs,
                frequency:         freq,
            });

            if (json.success) {
                state.priceCents    = json.data.monthly_price_cents;
                state.priceFormatted = json.data.monthly_price_formatted;
                $('jjpws-price-cents').value   = json.data.monthly_price_cents;
                $('jjpws-price-amount').textContent = json.data.monthly_price_formatted;
                if (preview) preview.style.display = 'block';
            }
        } catch { /* silently fail */ }
        finally {
            if (loading) loading.style.display = 'none';
        }
    }

    // ── Step 1 validation + next ───────────────────────────────────────────
    function validateStep1() {
        clearErrors();
        let valid = true;

        const street = $('jjpws-street')?.value.trim();
        const city   = $('jjpws-city')?.value.trim();
        const st     = $('jjpws-state')?.value.trim();
        const zip    = $('jjpws-zip')?.value.trim();

        if (!street || street.length < 5) valid = markError('jjpws-street', 'err-street', 'Please enter a valid street address.') || valid && false;
        if (!city)  valid = markError('jjpws-city',  'err-city',  'City is required.')   || valid && false;
        if (!st)    valid = markError('jjpws-state', 'err-state', 'State is required.')  || valid && false;
        if (!/^\d{5}$/.test(zip)) valid = markError('jjpws-zip', 'err-zip', 'Enter a valid 5-digit ZIP code.') || valid && false;

        // Lot size check
        const lotCat = $('jjpws-lot-category')?.value;
        const manualVisible = $('jjpws-lot-manual')?.style.display !== 'none';

        if (manualVisible) {
            const sel = $('jjpws-lot-manual-select')?.value;
            if (!sel) {
                valid = markError('jjpws-lot-manual-select', 'err-lot-manual', 'Please select your lot size.') && false;
            } else {
                state.lotCategory = sel;
                state.lotLabel    = jjpwsData.lotCategories[sel] || sel;
                setHiddenLot(sel, null);
            }
        } else if (!lotCat) {
            valid = false;
        }

        return valid;
    }

    function collectStep1() {
        state.street = $('jjpws-street')?.value.trim();
        state.city   = $('jjpws-city')?.value.trim();
        state.state  = $('jjpws-state')?.value.trim();
        state.zip    = $('jjpws-zip')?.value.trim();
        state.lat    = parseFloat($('jjpws-lat')?.value) || null;
        state.lng    = parseFloat($('jjpws-lng')?.value) || null;
        state.lotSqft     = parseInt($('jjpws-lot-sqft')?.value) || null;
        state.lotCategory = $('jjpws-lot-category')?.value || null;
    }

    // ── Step 2 validation + next ───────────────────────────────────────────
    function validateStep2() {
        clearErrors();
        let valid = true;

        if (!state.dogCount || state.dogCount < 1 || state.dogCount > 10) {
            fieldError('err-dogs', 'Dog count must be between 1 and 10.');
            valid = false;
        }

        if (!state.frequency) {
            fieldError('err-frequency', 'Please select a service frequency.');
            valid = false;
        }

        if (!state.priceCents) {
            valid = false; // price not loaded yet
        }

        return valid;
    }

    // ── Step 3 review populate ─────────────────────────────────────────────
    function populateReview() {
        const freqLabels = {
            twice_weekly: 'Twice a Week',
            weekly:       'Weekly',
            biweekly:     'Bi-Weekly',
        };
        const el = (id) => $(id);
        const address = [state.street, state.city, state.state, state.zip].filter(Boolean).join(', ');
        el('review-address').textContent   = address;
        el('review-lot').textContent       = state.lotLabel || state.lotCategory || '—';
        el('review-dogs').textContent      = state.dogCount;
        el('review-frequency').textContent = freqLabels[state.frequency] || state.frequency;
        el('review-price').textContent     = state.priceFormatted || '—';
    }

    // ── Checkout ───────────────────────────────────────────────────────────
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
            const json = await post('jjpws_create_checkout_session', {
                street:             state.street,
                city:               state.city,
                state:              state.state,
                zip:                state.zip,
                lat:                state.lat,
                lng:                state.lng,
                lot_size_sqft:      state.lotSqft,
                lot_size_category:  state.lotCategory,
                dog_count:          state.dogCount,
                frequency:          state.frequency,
                monthly_price_cents: state.priceCents,
            });

            if (json.success && json.data.checkout_url) {
                window.location.href = json.data.checkout_url;
            } else {
                const msg = json.data?.message || 'Something went wrong. Please try again.';
                errDiv.textContent   = msg;
                errDiv.style.display = 'block';
                btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
            }
        } catch {
            errDiv.textContent   = 'Network error. Please check your connection and try again.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            if (spinner) spinner.style.display = 'none';
        }
    }

    // ── Session storage (form resume) ──────────────────────────────────────
    const STORAGE_KEY = 'jjpws_form_state';

    function saveFormState() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch { /* quota/private mode */ }
    }

    function restoreFormState() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const saved = JSON.parse(raw);

            // Restore address fields
            if (saved.street) { $('jjpws-street').value = saved.street; state.street = saved.street; }
            if (saved.city)   { $('jjpws-city').value   = saved.city;   state.city   = saved.city;   }
            if (saved.state)  { $('jjpws-state').value  = saved.state;  state.state  = saved.state;  }
            if (saved.zip)    { $('jjpws-zip').value     = saved.zip;    state.zip    = saved.zip;    }

            if (saved.lat)   { $('jjpws-lat').value  = saved.lat;  state.lat  = saved.lat;  }
            if (saved.lng)   { $('jjpws-lng').value   = saved.lng;  state.lng  = saved.lng;  }
            if (saved.lotSqft)     { $('jjpws-lot-sqft').value     = saved.lotSqft;     state.lotSqft     = saved.lotSqft;     }
            if (saved.lotCategory) { $('jjpws-lot-category').value = saved.lotCategory; state.lotCategory = saved.lotCategory; }
            if (saved.lotLabel)    {
                state.lotLabel = saved.lotLabel;
                showLotResolved(saved.lotLabel);
            }

            if (saved.dogCount) {
                state.dogCount = saved.dogCount;
                const dc = $('jjpws-dog-count');
                if (dc) dc.value = saved.dogCount;
            }

            if (saved.frequency) {
                state.frequency = saved.frequency;
                const radios = qA('input[name="frequency"]');
                radios.forEach(r => (r.checked = r.value === saved.frequency));
            }

            if (saved.priceCents) {
                state.priceCents    = saved.priceCents;
                state.priceFormatted = saved.priceFormatted;
                $('jjpws-price-cents').value = saved.priceCents;
                $('jjpws-price-amount').textContent = saved.priceFormatted || '';
                const pp = $('jjpws-price-preview');
                if (pp) pp.style.display = 'block';
            }

            sessionStorage.removeItem(STORAGE_KEY);
            goToStep(3);
            populateReview();
        } catch { /* ignore */ }
    }

    // ── Stepper buttons ────────────────────────────────────────────────────
    function initStepper() {
        qS('.jjpws-stepper__btn--minus')?.addEventListener('click', () => {
            const inp = $('jjpws-dog-count');
            const v   = Math.max(1, parseInt(inp.value) - 1);
            inp.value     = v;
            state.dogCount = v;
            schedulePriceUpdate();
        });

        qS('.jjpws-stepper__btn--plus')?.addEventListener('click', () => {
            const inp = $('jjpws-dog-count');
            const v   = Math.min(10, parseInt(inp.value) + 1);
            inp.value     = v;
            state.dogCount = v;
            schedulePriceUpdate();
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────
    function init() {
        // Step 1 next
        $('jjpws-step1-next')?.addEventListener('click', async () => {
            if (!validateStep1()) return;
            collectStep1();

            // If lot not yet resolved (user filled manually without autocomplete), trigger lookup
            if (!state.lotCategory) {
                await lookupLotSize();
                if (!state.lotCategory) return; // still not resolved — manual required
            }

            goToStep(2);
            fetchPrice();
        });

        // Step 2 back / next
        $('jjpws-step2-back')?.addEventListener('click', () => goToStep(1));

        $('jjpws-step2-next')?.addEventListener('click', () => {
            if (!validateStep2()) return;
            populateReview();
            goToStep(3);
        });

        // Step 3 back
        $('jjpws-step3-back')?.addEventListener('click', () => goToStep(2));

        // Complete booking
        $('jjpws-complete-booking')?.addEventListener('click', handleCompleteBooking);

        // Frequency radios
        qA('input[name="frequency"]').forEach(r => {
            r.addEventListener('change', () => {
                state.frequency = r.value;
                schedulePriceUpdate();
            });
        });

        // Manual lot size select
        $('jjpws-lot-manual-select')?.addEventListener('change', (e) => {
            const v = e.target.value;
            state.lotCategory = v;
            state.lotLabel    = jjpwsData.lotCategories[v] || v;
            setHiddenLot(v, null);
        });

        // Address fields — trigger lot lookup on blur if no Google Autocomplete
        if (!jjpwsData.hasGoogle) {
            ['jjpws-zip'].forEach(id => {
                $(id)?.addEventListener('change', lookupLotSize);
            });
        }

        // Google Autocomplete event
        document.addEventListener('jjpws:addressSelected', () => {
            lookupLotSize();
        });

        // Stepper
        initStepper();

        // Resume flow after auth redirect
        if (jjpwsData.resumeFlow) {
            restoreFormState();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
