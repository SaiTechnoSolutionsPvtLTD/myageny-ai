/**
 * products-panel.js
 * =========================================================
 * Self-contained IIFE. Registers all functions on window.*
 * so onclick= attributes work even inside partials where
 * DOMContentLoaded has already fired.
 * =========================================================
 */
(function (PP) {
    'use strict';

    /* ── Config (injected from Blade via window.PP_CONFIG) ───────── */
    var cfg = window.PP_CONFIG || {};
    var LEAD_ID   = cfg.leadId   || 0;
    var API_BASE  = cfg.apiBase  || '/api/v1';
    var CSRF      = cfg.csrf     || '';
    var IS_ADMIN  = !!cfg.isAdmin;
    var STATUS_OPTIONS = normalizeStatusOptions(cfg.statusOptions || []);

    /* ── Local state ─────────────────────────────────────────────── */
    var ppState = {
        products    : [],   // catalogue fetched from GET /api/v1/products
        selected    : {},   // { product_id: { ...product, remarks, qty, disc } }
        deals       : [],   // fetched deals (accordion)
        summary     : {},
        activePayProdId : null,   // which lead_product is open in payment modal
        dealNameTouched : false,
        lastSuggestedDealName : '',
        editingProductId : null,
        production  : {
            leadProductId: null,
            detail: null,
            selectedDepartmentId: null,
            lastSubmission: null,
        },
        statusConfirm: {
            resolve: null,
        },
    };

    /* ─────────────────────────────────────────────────────────────
       UTILITY
    ───────────────────────────────────────────────────────────── */
    function fmt(n) {
        return '₹' + parseFloat(n || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    function el(id)   { return document.getElementById(id); }
    function qs(sel)  { return document.querySelector(sel); }
    function qsa(sel) { return document.querySelectorAll(sel); }

    /* ── Toast ───────────────────────────────────────────────────── */
    function toast(msg, type) {
        type = type || 'success';
        var wrap = el('pp-toast-wrap');
        if (!wrap) return;
        var t = document.createElement('div');
        t.className = 'pp-toast pp-toast--' + type;
        t.innerHTML = (type === 'success' ? '✓ ' : '✕ ') + msg;
        wrap.appendChild(t);
        setTimeout(function () { t.classList.add('pp-toast--out'); }, 2800);
        setTimeout(function () { t.remove(); }, 3200);
    }

    /* ── Spinner helpers ─────────────────────────────────────────── */
    function showLoader(id) {
        var e = el(id); if (e) { e.style.display = 'flex'; }
    }
    function hideLoader(id) {
        var e = el(id); if (e) { e.style.display = 'none'; }
    }

    /* ── API fetch wrapper ───────────────────────────────────────── */
    function api(method, path, body) {
        var opts = {
            method  : method,
            headers : {
                'Content-Type' : 'application/json',
                'Accept'       : 'application/json',
                'X-CSRF-TOKEN' : CSRF,
            },
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(API_BASE + path, opts).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw data;
                return data;
            });
        });
    }

    function apiFormData(path, formData) {
        return fetch(API_BASE + path, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: formData,
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw data;
                return data;
            });
        });
    }

    /* ─────────────────────────────────────────────────────────────
       MODAL HELPERS
    ───────────────────────────────────────────────────────────── */
    var MODALS = ['pp-modal-add-product', 'pp-modal-payment', 'pp-modal-history', 'pp-modal-production', 'pp-modal-status-confirm'];

    function ppShow(id) {
        var e = el(id);
        if (e) { e.classList.add('pp-show'); document.body.style.overflow = 'hidden'; }
    }

    PP.ppHideModal = function (id) {
        var e = el(id);
        if (e) { e.classList.remove('pp-show'); document.body.style.overflow = ''; }
        if (id === 'pp-modal-payment') {
            handlePaymentModalClosed();
        } else if (id === 'pp-modal-status-confirm') {
            resolveStatusConfirm(false);
        }
    };

    function showStatusConfirm(label) {
        var labelEl = el('pp-status-confirm-label');
        if (labelEl) {
            labelEl.textContent = 'Change status to ' + label;
        }

        return new Promise(function (resolve) {
            ppState.statusConfirm.resolve = resolve;
            ppShow('pp-modal-status-confirm');
        });
    }

    function resolveStatusConfirm(confirmed) {
        var resolver = ppState.statusConfirm.resolve;
        ppState.statusConfirm.resolve = null;

        var modal = el('pp-modal-status-confirm');
        if (modal) {
            modal.classList.remove('pp-show');
            document.body.style.overflow = '';
        }

        if (resolver) {
            resolver(!!confirmed);
        }
    }

    PP.ppResolveStatusConfirm = resolveStatusConfirm;

    /* Close on backdrop */
    MODALS.forEach(function (id) {
        document.addEventListener('click', function (e) {
            var modal = el(id);
            if (modal && e.target === modal) PP.ppHideModal(id);
        });
    });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') MODALS.forEach(PP.ppHideModal);
    });

    /* ─────────────────────────────────────────────────────────────
       ADD PRODUCT MODAL
    ───────────────────────────────────────────────────────────── */

    /** Open modal and fetch catalogue if needed */
    PP.ppShowAddProduct = function () {
        resetAddModal();
        setAddModalMode(false);
        ppShow('pp-modal-add-product');
        if (ppState.products.length === 0) {
            fetchProductCatalogue();
        } else {
            renderProductMultiSelect();
        }
    };

    function resetAddModal() {
        var dealInp = el('pp-deal-name');
        if (dealInp) {
            dealInp.value = '';
            dealInp.dataset.autoSuggested = '';
        }
        ppState.selected = {};
        ppState.dealNameTouched = false;
        ppState.lastSuggestedDealName = '';
        ppState.editingProductId = null;
        var multiSel = el('pp-product-multi-select');
        if (multiSel) {
            multiSel.disabled = false;
            Array.from(multiSel.options).forEach(function (o) { o.selected = false; });
        }
        renderSelectedTable();
    }

    function setAddModalMode(isEdit) {
        setInner('pp-add-product-modal-title', isEdit ? 'Edit Product' : '📦 Add Product to Lead');

        var submitBtn = el('pp-submit-deal-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                '<polyline points="20 6 9 17 4 12"/></svg>' +
                (isEdit ? 'Update Product' : 'Create Deal');
        }

        var priceRequestBtn = el('pp-submit-price-request-btn');
        if (priceRequestBtn) {
            priceRequestBtn.style.display = isEdit ? 'none' : '';
        }

        var multiSel = el('pp-product-multi-select');
        if (multiSel) {
            multiSel.disabled = false;
        }
    }

    PP.ppShowEditProduct = function (prodId) {
        var p = findProduct(prodId);
        if (!p) { toast('Product not found. Try refreshing.', 'error'); return; }

        resetAddModal();
        setAddModalMode(true);
        ppState.editingProductId = prodId;

        var dealInp = el('pp-deal-name');
        if (dealInp) dealInp.value = p.deal_name || '';

        ppState.selected[p.product_id || p.id] = {
            id           : p.product_id || p.id,
            leadProductId: p.id,
            name         : p.name,
            description  : p.description || '',
            price        : parseFloat(p.unit_price || 0),
            originalPrice: parseFloat(p.unit_price || 0),
            qty          : parseInt(p.quantity || 1, 10),
            disc         : parseFloat(p.discount_percent || 0),
            remarks      : p.remarks || '',
        };

        if (ppState.products.length === 0) {
            fetchProductCatalogue();
        } else {
            syncEditProductSelect(p.product_id);
        }

        renderSelectedTable();
        ppShow('pp-modal-add-product');
    };

    function fetchProductCatalogue() {
        showLoader('pp-product-loading');
        api('GET', '/products?lead_id=' + LEAD_ID).then(function (res) {
            ppState.products = res.data || [];
            renderProductMultiSelect();
        }).catch(function () {
            toast('Failed to load products', 'error');
        }).finally(function () {
            hideLoader('pp-product-loading');
        });
    }

    function renderProductMultiSelect() {
        var sel = el('pp-product-multi-select');
        if (!sel) return;
        sel.innerHTML = '';
        ppState.products.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = (p.category ? p.category + ' | ' : '') + p.name + ' — ' + fmt(p.price);
            opt.dataset.product = JSON.stringify(p);
            sel.appendChild(opt);
        });
        if (ppState.editingProductId) {
            var editing = findProduct(ppState.editingProductId);
            syncEditProductSelect(editing && editing.product_id);
        }
    }

    function syncEditProductSelect(productId) {
        var sel = el('pp-product-multi-select');
        if (!sel || !productId) return;
        Array.from(sel.options).forEach(function (o) {
            o.selected = parseInt(o.value, 10) === parseInt(productId, 10);
        });
    }

    function selectedProductDraft() {
        var ids = Object.keys(ppState.selected);
        return ids.length ? ppState.selected[ids[0]] : null;
    }

    function buildSelectedProductState(product, current) {
        current = current || {};
        var basePrice = parseFloat(product.price || 0);

        return {
            id            : product.id,
            leadProductId : current.leadProductId || null,
            name          : product.name,
            description   : product.description || '',
            price         : current.price != null ? parseFloat(current.price) || 0 : basePrice,
            originalPrice : current.originalPrice != null ? parseFloat(current.originalPrice) || 0 : basePrice,
            qty           : current.qty != null ? parseInt(current.qty, 10) || 1 : 1,
            disc          : current.disc != null ? parseFloat(current.disc) || 0 : parseFloat(product.discount_percent || 0),
            remarks       : current.remarks || '',
        };
    }

    function handleEditProductSelectionChange(sel) {
        var selectedOpt = sel.selectedOptions[sel.selectedOptions.length - 1] || null;
        var current = selectedProductDraft();

        ppState.selected = {};

        if (!selectedOpt) {
            renderSelectedTable();
            return;
        }

        Array.from(sel.options).forEach(function (opt) {
            opt.selected = opt === selectedOpt;
        });

        var product = JSON.parse(selectedOpt.dataset.product);
        ppState.selected[product.id] = buildSelectedProductState(product, {
            leadProductId: current && current.leadProductId,
            qty          : current && current.qty,
            disc         : current && current.disc,
            remarks      : current && current.remarks,
        });
    }

    /** Called when multi-select changes */
    PP.ppOnProductSelect = function () {
        var sel = el('pp-product-multi-select');
        if (!sel) return;

        if (ppState.editingProductId) {
            handleEditProductSelectionChange(sel);
            renderSelectedTable();
            return;
        }

        // Add newly selected
        Array.from(sel.selectedOptions).forEach(function (opt) {
            var pid = parseInt(opt.value);
            if (!ppState.selected[pid]) {
                var p = JSON.parse(opt.dataset.product);
                ppState.selected[pid] = buildSelectedProductState(p);
            }
        });

        // Remove deselected
        var selIds = Array.from(sel.selectedOptions).map(function (o) { return parseInt(o.value); });
        Object.keys(ppState.selected).forEach(function (pid) {
            if (!selIds.includes(parseInt(pid))) delete ppState.selected[pid];
        });

        syncSuggestedDealName();
        renderSelectedTable();
    };

    function renderSelectedTable() {
        var wrap = el('pp-selected-products-wrap');
        var tbody = el('pp-selected-tbody');
        if (!wrap || !tbody) return;

        var ids = Object.keys(ppState.selected);
        wrap.style.display = ids.length > 0 ? 'block' : 'none';
        if (ids.length === 0) { tbody.innerHTML = ''; return; }

        tbody.innerHTML = ids.map(function (pid) {
            var p = ppState.selected[pid];
            var total = p.price * p.qty * (1 - p.disc / 100);
            return '<tr data-pid="' + pid + '">' +
                '<td class="pp-td-name"><strong>' + escHtml(p.name) + '</strong>' +
                    '<div class="pp-td-desc">' + escHtml(p.description) + '</div></td>' +
                '<td class="pp-td-price">' +
                    '<input type="number" class="ppf-inp ni" value="' + p.price + '" step="0.01" min="0" ' +
                    'data-pid="' + pid + '" onchange="PP.ppUpdateRow(this,\'price\')">' +
                    '<div class="pp-td-desc">Base ' + fmt(p.originalPrice) + '</div>' +
                '</td>' +
                '<td class="pp-td-qty">' +
                    '<input type="number" class="ppf-inp ni pp-qty-inp" value="' + p.qty + '" ' +
                    'min="1" data-pid="' + pid + '" onchange="PP.ppUpdateRow(this,\'qty\')">' +
                '</td>' +
                '<td class="pp-td-disc">' +
                    '<input type="number" class="ppf-inp ni pp-disc-inp" value="' + p.disc + '" ' +
                    'min="0" max="100" data-pid="' + pid + '" onchange="PP.ppUpdateRow(this,\'disc\')">' +
                '</td>' +
                '<td class="pp-td-total"><strong>' + fmt(total) + '</strong></td>' +
                '<td class="pp-td-remarks">' +
                    '<input type="text" class="ppf-inp ni" placeholder="Remarks…" ' +
                    'value="' + escHtml(p.remarks) + '" data-pid="' + pid + '" ' +
                    'onchange="PP.ppUpdateRow(this,\'remarks\')">' +
                '</td>' +
                '<td><button type="button" class="pp-remove-row" onclick="PP.ppRemoveSelected(' + pid + ')" title="Remove">' +
                    '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button></td>' +
            '</tr>';
        }).join('');
    }

    PP.ppUpdateRow = function (inp, field) {
        var pid = inp.dataset.pid;
        if (!ppState.selected[pid]) return;
        ppState.selected[pid][field] = field === 'remarks' ? inp.value : parseFloat(inp.value) || 0;
        // Re-render just the total cell
        var row   = inp.closest('tr');
        var totalEl = row ? row.querySelector('.pp-td-total strong') : null;
        if (totalEl) {
            var p     = ppState.selected[pid];
            var total = p.price * p.qty * (1 - p.disc / 100);
            totalEl.textContent = fmt(total);
        }
    };

    PP.ppRemoveSelected = function (pid) {
        delete ppState.selected[pid];
        var sel = el('pp-product-multi-select');
        if (sel) {
            Array.from(sel.options).forEach(function (o) {
                if (parseInt(o.value) === parseInt(pid)) o.selected = false;
            });
        }
        syncSuggestedDealName();
        renderSelectedTable();
    };

    function syncSuggestedDealName() {
        var dealInp = el('pp-deal-name');
        if (!dealInp) return;

        var suggested = buildSuggestedDealName();
        ppState.lastSuggestedDealName = suggested;

        if (!suggested) {
            if (!ppState.dealNameTouched || dealInp.value === dealInp.dataset.autoSuggested) {
                dealInp.value = '';
                dealInp.dataset.autoSuggested = '';
            }
            return;
        }

        if (!ppState.dealNameTouched || !dealInp.value.trim() || dealInp.value === dealInp.dataset.autoSuggested) {
            dealInp.value = suggested;
            dealInp.dataset.autoSuggested = suggested;
        }
    }

    function buildSuggestedDealName() {
        var selectedCount = Object.keys(ppState.selected).length;
        if (!selectedCount) {
            return '';
        }
        return currentMonthShortName() + ' Deal';
    }

    function currentMonthShortName() {
        return new Date().toLocaleString('en-US', { month: 'short' });
    }

    /** Submit the Add Product form */
    PP.ppSubmitDeal = function () {
        var dealName = (el('pp-deal-name') || {}).value || '';
        if (!dealName.trim()) { toast('Please enter a Deal Name.', 'error'); return; }

        var products = Object.values(ppState.selected);
        if (products.length === 0) { toast('Select at least one product.', 'error'); return; }

        if (ppState.editingProductId) {
            submitProductEdit(dealName.trim(), products[0]);
            return;
        }

        if (!IS_ADMIN && hasPriceChange(products)) {
            toast('Price was changed. Please send a price change request for admin approval.', 'error');
            return;
        }

        var btnEl = el('pp-submit-deal-btn');
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'Saving…'; }

        api('POST', '/lead-products', {
            lead_id   : LEAD_ID,
            deal_name : dealName.trim(),
            products  : products.map(function (p) {
                return {
                    product_id       : p.id,
                    unit_price       : p.price,
                    quantity         : p.qty,
                    discount_percent : p.disc,
                    remarks          : p.remarks,
                };
            }),
        })
        .then(function () {
            PP.ppHideModal('pp-modal-add-product');
            toast('Deal "' + dealName + '" created!');
            loadDeals();
        })
        .catch(function (err) {
            var msg = (err.errors && Object.values(err.errors)[0]) || err.message || 'Something went wrong.';
            toast(msg, 'error');
        })
        .finally(function () {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Create Deal'; }
        });
    };

    function submitProductEdit(dealName, product) {
        if (!product) { toast('Select a product to update.', 'error'); return; }

        var btnEl = el('pp-submit-deal-btn');
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'Updating…'; }

        api('PUT', '/lead-products/' + ppState.editingProductId, {
            deal_name        : dealName,
            product_id       : product.id,
            unit_price       : product.price,
            quantity         : product.qty,
            discount_percent : product.disc,
            remarks          : product.remarks,
        })
        .then(function () {
            PP.ppHideModal('pp-modal-add-product');
            toast('Product updated!');
            ppProductCache = {};
            loadDeals();
        })
        .catch(function (err) {
            var msg = firstErrorMessage(err) || err.message || 'Failed to update product.';
            toast(msg, 'error');
        })
        .finally(function () {
            if (btnEl) { btnEl.disabled = false; }
            setAddModalMode(false);
        });
    }

    PP.ppSubmitPriceRequest = function () {
        var dealName = (el('pp-deal-name') || {}).value || '';
        if (!dealName.trim()) { toast('Please enter a Deal Name.', 'error'); return; }

        var products = Object.values(ppState.selected);
        if (products.length === 0) { toast('Select at least one product.', 'error'); return; }
        if (!hasPriceChange(products)) {
            toast('Change at least one product price before sending a request.', 'error');
            return;
        }

        var btnEl = el('pp-submit-price-request-btn');
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'Sending…'; }

        api('POST', '/lead-product-price-requests', {
            lead_id   : LEAD_ID,
            deal_name : dealName.trim(),
            products  : products.filter(function (p) {
                return normalizePrice(p.price) !== normalizePrice(p.originalPrice);
            }).map(function (p) {
                return {
                    product_id            : p.id,
                    requested_unit_price  : p.price,
                    quantity              : p.qty,
                    discount_percent      : p.disc,
                    remarks               : p.remarks,
                };
            }),
        })
        .then(function () {
            PP.ppHideModal('pp-modal-add-product');
            toast('Price request sent to admin.');
        })
        .catch(function (err) {
            var msg = (err.errors && Object.values(err.errors)[0]) || err.message || 'Failed to send request.';
            toast(msg, 'error');
        })
        .finally(function () {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Send Price Request'; }
        });
    };

    /* ─────────────────────────────────────────────────────────────
       DEAL ACCORDION — LOAD & RENDER
    ───────────────────────────────────────────────────────────── */
    function loadDeals() {
        showLoader('pp-deals-loading');
        var container = el('pp-deals-container');
        if (container) container.style.opacity = '0.5';

        api('GET', '/lead-products/' + LEAD_ID)
        .then(function (res) {
            if (res.statuses && res.statuses.length) {
                STATUS_OPTIONS = normalizeStatusOptions(res.statuses);
                STATUS_CONFIG = buildStatusConfig(STATUS_OPTIONS);
            }
            ppProductCache = {};
            ppState.deals   = res.deals   || [];
            ppState.summary = res.summary || {};
            renderSummaryBar();
            renderDeals();
        })
        .catch(function () {
            toast('Failed to refresh deals.', 'error');
        })
        .finally(function () {
            hideLoader('pp-deals-loading');
            var c = el('pp-deals-container');
            if (c) c.style.opacity = '1';
        });
    }

    function renderSummaryBar() {
        var s = ppState.summary;
        setInner('pp-sum-total',   fmt(s.total_value));
        setInner('pp-sum-paid',    fmt(s.total_paid));
        setInner('pp-sum-pending', fmt(s.total_pending));
        setInner('pp-sum-count',   s.product_count || 0);
        setInner('pp-sum-converted', (s.converted || 0) + ' of ' + (s.product_count || 0));
    }

    function renderDeals() {
        var container = el('pp-deals-container');
        if (!container) return;

        if (!ppState.deals.length) {
            container.innerHTML = renderEmptyState();
            return;
        }

        container.innerHTML = ppState.deals.map(function (deal, di) {
            return renderDealAccordion(deal, di);
        }).join('');

        // Expand first deal by default
        var firstBody = container.querySelector('.pp-deal-body');
        if (firstBody) firstBody.style.display = 'block';
        var firstChev = container.querySelector('.pp-deal-chevron');
        if (firstChev) firstChev.style.transform = 'rotate(180deg)';
    }

    function renderDealAccordion(deal, di) {
        var progress  = deal.total_value > 0
            ? Math.min(100, Math.round((deal.total_paid / deal.total_value) * 100))
            : 0;
        var prgColor  = progress >= 100 ? '#16a34a' : (progress > 0 ? '#fe5f04' : '#e1dee3');

        return '<div class="pp-deal-accordion" data-deal="' + escAttr(deal.deal_name) + '">' +

            // ── Header
            '<div class="pp-deal-header" onclick="PP.ppToggleDeal(this)">' +
                '<span class="pp-deal-chevron">▼</span>' +
                '<div class="pp-deal-icon">🤝</div>' +
                '<div class="pp-deal-info">' +
                    '<div class="pp-deal-name">' + escHtml(deal.deal_name) + '</div>' +
                    '<div class="pp-deal-meta">' + deal.products.length + ' product(s) · ' + fmt(deal.total_value) + '</div>' +
                '</div>' +
                '<div class="pp-deal-right">' +
                    // Amounts pill
                    '<div class="pp-deal-amounts">' +
                        '<span class="pp-pill pp-pill-green">' + fmt(deal.total_paid) + ' paid</span>' +
                        (deal.total_pending > 0
                            ? '<span class="pp-pill pp-pill-red">' + fmt(deal.total_pending) + ' due</span>'
                            : '<span class="pp-pill pp-pill-green">Settled ✓</span>') +
                    '</div>' +
                '</div>' +
            '</div>' +

            // ── Progress
            '<div class="pp-deal-progress" style="padding:0 18px 0">' +
                '<div class="pp-progress-bar-outer" style="margin-bottom:4px">' +
                    '<div class="pp-progress-bar-inner" style="width:' + progress + '%;background:' + prgColor + '"></div>' +
                '</div>' +
            '</div>' +

            // ── Body (collapsible)
            '<div class="pp-deal-body" style="display:none">' +
                deal.products.map(function (p) {
                    return renderProductCard(p);
                }).join('') +
            '</div>' +

        '</div>';
    }

    function renderProductCard(p) {
        var statusValue = String(p.status_id || p.status_value || '');
        var statusCfg = getStatusConfig(statusValue, p.status_label);
        var statusOptions = getStatusOptions(statusValue, p.status_label);
        var currentStatusKey = statusKey(p.status_label || p.status_value || '');
        var isConverted = isConvertedProduct(p);
        var progress  = p.total > 0 ? Math.min(100, Math.round((p.paid / p.total) * 100)) : 0;
        var prgColor  = progress >= 100 ? '#16a34a' : (progress > 0 ? '#fe5f04' : '#e1dee3');
        var pending   = p.total - p.paid;
        var pendingClr = pending > 0 ? '#dc2626' : '#16a34a';
        var productionMeta = '';
        var productNameHtml = '<span class="pp-prod-name">' + escHtml(p.name) + '</span>';

        if (p.productionInitiation) {
            var movedText = p.productionInitiation.department_name
                ? 'Moved to ' + escHtml(p.productionInitiation.department_name)
                : 'Moved to Production';
            var dateText = p.productionInitiation.moved_at
                ? ' | ' + escHtml(p.productionInitiation.moved_at)
                : '';

            if (p.productionInitiation.view_url) {
                productNameHtml = '<a class="pp-prod-name" href="' + escAttr(p.productionInitiation.view_url) + '" style="color:#047857;text-decoration:none">' +
                    escHtml(p.name) +
                '</a>';
            }

            productionMeta = '<div class="pp-prod-desc-sub" style="margin-top:6px;color:#047857;font-weight:700">' +
                'Product initiate completed | ' + movedText + dateText +
            '</div>';
        }

        return '<div class="pp-prod-card pp-prod-card--sub" id="pp-prod-' + p.id + '">' +
            '<div class="pp-prod-inner">' +
                '<div class="pp-prod-name-row">' +
                    productNameHtml +
                    productionMeta +
                '</div>' +
                '<div style="display:flex;justify-content:flex-end;margin:0 0 12px;">' +
                    '<div class="pp-status-select-wrap">' +
                        '<select class="pp-status-select" ' +
                            'style="background:' + statusCfg.bg + ';color:' + statusCfg.text + ';border-color:' + statusCfg.border + '" ' +
                            'data-product="' + p.id + '" ' +
                            (isConverted ? 'disabled title="Converted products are locked"' : '') + ' ' +
                            'onchange="PP.ppUpdateProductStatus(this)">' +
                            statusOptions.map(function (option) {
                                var value = String(option.id);
                                var sc = getStatusConfig(value, option.name);
                                return '<option value="' + escAttr(value) + '" ' + (statusValue === value ? 'selected' : '') + '>' +
                                    optionText(sc.icon, option.name) + '</option>';
                            }).join('') +
                        '</select>' +
                        '<svg class="pp-status-caret" style="color:' + statusCfg.text + '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
                    '</div>' +
                '</div>' +
                '<div class="pp-amounts-row">' +
                    '<div class="pp-amt-item"><div class="pp-amt-label">Total</div>' +
                        '<div class="pp-amt-value">' + fmt(p.total) + '</div></div>' +
                    '<div class="pp-amt-item"><div class="pp-amt-label" style="color:#16a34a">Paid</div>' +
                        '<div class="pp-amt-value" style="color:#16a34a">' + fmt(p.paid) + '</div></div>' +
                    '<div class="pp-amt-item"><div class="pp-amt-label" style="color:' + pendingClr + '">Pending</div>' +
                        '<div class="pp-amt-value" style="color:' + pendingClr + '">' + fmt(pending) + '</div></div>' +
                '</div>' +
                '<div class="pp-progress-wrap">' +
                    '<div class="pp-progress-bar-outer"><div class="pp-progress-bar-inner" style="width:' + progress + '%;background:' + prgColor + '"></div></div>' +
                    '<div class="pp-progress-label"><span>' + p.payments.length + ' payment(s)</span>' +
                        '<span style="color:' + prgColor + ';font-weight:700">' + progress + '% collected</span></div>' +
                '</div>' +
                '<div class="pp-prod-footer">' +
                    '<div class="pp-footer-actions">' +
                        '<button type="button" class="pp-act-btn pp-btn-pay" ' +
                            (isConverted
                                ? 'onclick="PP.ppShowPayment(' + p.id + ')"'
                                : 'disabled style="opacity:.55;cursor:not-allowed" title="' + escAttr(paymentLockedMessage()) + '"') +
                            '>' +
                            '' +
                            'Add Payment' +
                        '</button>' +
                        '<button type="button" class="pp-act-btn pp-btn-hist" onclick="PP.ppShowHistory(' + p.id + ')">' +
                            '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="12 8 12 12 14 14"/><circle cx="12" cy="12" r="10"/></svg>' +
                            'History (' + p.payments.length + ')' +
                        '</button>' +
                        '<button type="button" class="pp-act-btn pp-btn-edit" ' +
                            (isConverted
                                ? 'disabled style="opacity:.55;cursor:not-allowed" title="Converted products cannot be edited"'
                                : 'onclick="PP.ppShowEditProduct(' + p.id + ')"') +
                            '>' +
                            '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>' +
                            'Edit' +
                        '</button>' +
                        (p.productionInitiation
                            ? '<a class="pp-act-btn pp-btn-prod" href="' + escAttr(p.productionInitiation.view_url || '#') + '" style="opacity:.9">' +
                                '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>' +
                                'Product Initiated' +
                              '</a>'
                            : !canMoveToProduction(p)
                                ? '<button type="button" class="pp-act-btn pp-btn-prod" disabled style="opacity:.55;cursor:not-allowed" title="' + escAttr(productionLockedMessage(p)) + '">' +
                                    '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 7h18"/><path d="M6 12h12"/><path d="M9 17h6"/></svg>' +
                                    'Move to Production' +
                                  '</button>'
                            : '<button type="button" class="pp-act-btn pp-btn-prod" onclick="PP.ppShowProduction(' + p.id + ')">' +
                                '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 7h18"/><path d="M6 12h12"/><path d="M9 17h6"/></svg>' +
                                'Move to Production' +
                              '</button>') +
                        '<button type="button" class="pp-act-btn pp-btn-del" ' +
                            'onclick="PP.ppDeleteProduct(' + p.id + ',\'' + escAttr(p.name) + '\')">' +
                            '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function renderEmptyState() {
        return '<div class="pp-empty-state">' +
            '<div class="pp-empty-icon">📦</div>' +
            '<div class="pp-empty-title">No deals yet</div>' +
            '<div class="pp-empty-sub">Click "Add Product" to create your first deal</div>' +
        '</div>';
    }

    /* ─────────────────────────────────────────────────────────────
       ACCORDION TOGGLE
    ───────────────────────────────────────────────────────────── */
    PP.ppToggleDeal = function (header) {
        var accordion = header.closest('.pp-deal-accordion');
        var body      = accordion.querySelector('.pp-deal-body');
        var chev      = header.querySelector('.pp-deal-chevron');
        var open      = body.style.display !== 'none';
        body.style.display  = open ? 'none' : 'block';
        chev.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
    };

    /* ─────────────────────────────────────────────────────────────
       STATUS UPDATE
    ───────────────────────────────────────────────────────────── */
    PP.ppUpdateProductStatus = function (sel) {
        var productId = sel.dataset.product;
        var statusId = sel.value;
        var product = findProduct(parseInt(productId, 10));
        var currentStatusKey = statusKey(product && (product.status_label || product.status_value || ''));
        var previousValue = product ? String(product.status_id || product.status_value || '') : '';
        var option   = getStatusOption(statusId);
        var label    = option ? option.name : statusId;
        var cfg      = getStatusConfig(statusId, label);

        if (!product) {
            sel.value = previousValue;
            toast('Product not found. Try refreshing.', 'error');
            return;
        }

        if (String(statusId) === previousValue) {
            return;
        }

        if (currentStatusKey === 'converted') {
            sel.value = previousValue;
            toast('Converted product status cannot be changed again.', 'error');
            loadDeals();
            return;
        }

        showStatusConfirm(label).then(function (confirmed) {
            if (!confirmed) {
                sel.value = previousValue;
                return;
            }

            // Optimistic UI
            sel.style.background   = cfg.bg;
            sel.style.color        = cfg.text;
            sel.style.borderColor  = cfg.border;
            var caret = sel.parentNode.querySelector('.pp-status-caret');
            if (caret) caret.style.color = cfg.text;

            var payload = {
                lead_id        : LEAD_ID,
                product_id     : productId,
            };
            if (/^\d+$/.test(statusId)) {
                payload.lead_status_id = statusId;
            } else {
                payload.product_status = statusId;
            }

            submitProductStatusChange(payload, label)
            .then(function () { loadDeals(); })
            .catch(function () {
                sel.value = previousValue;
                toast('Failed to update status', 'error');
                loadDeals();
            });
        });
    };

    function submitProductStatusChange(payload, fallbackLabel) {
        return api('PUT', '/lead-products/status', payload).then(function (res) {
            var label = fallbackLabel;

            if (res.status) {
                var updated = normalizeStatusOptions([res.status])[0];
                if (updated && !getStatusOption(updated.id)) {
                    STATUS_OPTIONS.push(updated);
                    STATUS_CONFIG = buildStatusConfig(STATUS_OPTIONS);
                }
                label = updated ? updated.name : label;
            }

            toast('Status updated to ' + label);
            return res;
        });
    }

    /* ─────────────────────────────────────────────────────────────
       PAYMENT MODAL
    ───────────────────────────────────────────────────────────── */
    var ppProductCache = {};   // { id: jsPayload } to avoid re-searching nested structure

    function findProduct(id) {
        if (ppProductCache[id]) return ppProductCache[id];
        for (var di = 0; di < ppState.deals.length; di++) {
            for (var pi = 0; pi < ppState.deals[di].products.length; pi++) {
                var p = ppState.deals[di].products[pi];
                if (p.id === id) { ppProductCache[id] = p; return p; }
            }
        }
        return null;
    }

    function isConvertedProduct(product) {
        return statusKey(product && (product.status_label || product.status_value || '')) === 'converted';
    }

    function paymentLockedMessage() {
        return 'Convert the product status first to enable payments.';
    }

    function productionLockedMessage(product) {
        if (!isConvertedProduct(product)) {
            return 'Convert the product first to initiate production.';
        }

        if ((product.payments || []).length < 1) {
            return 'At least 1 received payment is required before moving this product to production.';
        }

        return '';
    }

    function canOpenPaymentModal(product) {
        return isConvertedProduct(product);
    }

    function canMoveToProduction(product) {
        return isConvertedProduct(product) && (product.payments || []).length >= 1;
    }

    PP.ppShowPayment = function (prodId) {
        var p = findProduct(prodId);
        if (!p) { toast('Product not found. Try refreshing.', 'error'); return; }
        if (!canOpenPaymentModal(p)) {
            toast(paymentLockedMessage(), 'error');
            return;
        }

        ppState.activePayProdId = prodId;

        setInner('pp-pay-name',    p.name);
        setInner('pp-pay-total',   fmt(p.total));
        setInner('pp-pay-paid',    fmt(p.paid));
        setInner('pp-pay-balance', fmt(p.total - p.paid));

        var amtInp = el('pp-pay-amount');
        if (amtInp) amtInp.value = (p.total - p.paid) > 0
            ? (p.total - p.paid).toFixed(2) : '';

        // Reset mode to UPI
        qsa('.ppf-mode-tile').forEach(function (t) { t.classList.remove('pp-sel'); });
        var upi = qs('[data-val="upi"].ppf-mode-tile');
        if (upi) upi.classList.add('pp-sel');
        var modeInp = el('pp-mode-val');
        if (modeInp) modeInp.value = 'upi';

        // Reset date to today
        var dateInp = el('pp-pay-date');
        if (dateInp) dateInp.value = todayStr();

        var refInp = el('pp-pay-ref');
        if (refInp) refInp.value = '';

        var notesInp = el('pp-pay-notes');
        if (notesInp) notesInp.value = '';

        var fileInp = el('pp-pay-attachment');
        if (fileInp) fileInp.value = '';

        ppShow('pp-modal-payment');
    };

    PP.ppSubmitPayment = function () {
        var pid    = ppState.activePayProdId;
        var amount = parseFloat((el('pp-pay-amount') || {}).value || 0);
        var mode   = (el('pp-mode-val')     || {}).value || 'upi';
        var date   = (el('pp-pay-date')     || {}).value || todayStr();
        var ref    = (el('pp-pay-ref')      || {}).value || '';
        var notes  = (el('pp-pay-notes')    || {}).value || '';
        var fileInp = el('pp-pay-attachment');
        var file = fileInp && fileInp.files ? fileInp.files[0] : null;

        if (!pid)         { toast('No product selected.', 'error'); return; }
        if (amount <= 0)  { toast('Enter a valid amount.', 'error'); return; }

        var btnEl = el('pp-submit-pay-btn');
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'Saving…'; }

        var formData = new FormData();
        formData.append('lead_product_id', pid);
        formData.append('amount', amount);
        formData.append('payment_mode', mode);
        formData.append('payment_date', date);
        formData.append('reference_number', ref);
        formData.append('notes', notes);
        if (file) {
            formData.append('attachment', file);
        }

        apiFormData('/payments', formData)
        .then(function () {
            return null;
        })
        .then(function () {
            PP.ppHideModal('pp-modal-payment');
            toast('Payment of ' + fmt(amount) + ' recorded!');
            ppProductCache = {};   // clear cache
            loadDeals();
        })
        .catch(function (err) {
            var msg = (err.errors && Object.values(err.errors)[0]) || err.message || 'Error saving payment.';
            toast(msg, 'error');
        })
        .finally(function () {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Save Payment'; }
        });
    };

    /* ─────────────────────────────────────────────────────────────
       PAYMENT HISTORY MODAL
    ───────────────────────────────────────────────────────────── */
    PP.ppShowHistory = function (prodId) {
        setInner('pp-hist-body', '<div class="pp-hist-loading"><div class="pp-spinner"></div></div>');
        ppShow('pp-modal-history');

        api('GET', '/payments/' + prodId)
        .then(function (res) {
            console.log('PP.payments response:', res);
            var p = res.product;
            setInner('pp-hist-name', p.name);
            var canAddPayment = isConvertedProduct(p);
            var addBtn = el('pp-hist-add-btn');
            if (addBtn) {
                addBtn.disabled = !canAddPayment;
                addBtn.style.opacity = canAddPayment ? '' : '.55';
                addBtn.style.cursor = canAddPayment ? '' : 'not-allowed';
                addBtn.title = canAddPayment ? '' : paymentLockedMessage();
                addBtn.onclick = canAddPayment ? function () {
                    PP.ppHideModal('pp-modal-history');
                    PP.ppShowPayment(prodId);
                } : null;
            }

            setInner('pp-hist-body', renderHistoryBody(p, res.overall || []));
        })
        .catch(function () {
            setInner('pp-hist-body', '<div class="pp-hist-empty"><div class="pp-hist-empty-ico">⚠️</div><div>Failed to load history.</div></div>');
        });
    };

    PP.ppShowProduction = function (prodId) {
        var p = findProduct(prodId);
        if (!p) { toast('Product not found. Try refreshing.', 'error'); return; }

        if (!canMoveToProduction(p)) {
            toast(productionLockedMessage(p), 'error');
            return;
        }

        ppState.production.leadProductId = prodId;
        ppState.production.detail = null;
        ppState.production.selectedDepartmentId = null;
        ppState.production.lastSubmission = null;

        setInner('pp-production-name', 'Loading...');
        setInner('pp-production-body', '<div class="pp-production-empty"><div class="pp-production-empty-title">Loading production mapping</div><div class="pp-production-empty-copy">Please wait while we load the mapped departments and workflow stages.</div></div>');
        ppShow('pp-modal-production');

        api('GET', '/lead-products/' + prodId + '/production')
            .then(function (res) {
                var departments = Array.isArray(res.departments) ? res.departments : [];
                var preferredDepartment = departments.find(function (department) {
                    return department.is_development;
                }) || departments[0] || null;

                ppState.production.detail = res;
                ppState.production.selectedDepartmentId = preferredDepartment ? preferredDepartment.id : null;
                setInner('pp-production-name', escHtml((res.product && res.product.name) || p.name || 'Product'));
                renderProductionBody();
            })
            .catch(function (err) {
                var msg = err && err.message ? err.message : 'Failed to load production mapping.';
                setInner('pp-production-name', escHtml(p.name || 'Product'));
                setInner('pp-production-body', '<div class="pp-production-empty"><div class="pp-production-empty-title">Unable to load production mapping</div><div class="pp-production-empty-copy">' + escHtml(msg) + '</div></div>');
                toast(msg, 'error');
            });
    };

    PP.ppOnProductionDepartmentChange = function (value) {
        ppState.production.selectedDepartmentId = value ? parseInt(value, 10) : null;
        renderProductionBody();
    };

    PP.ppSubmitProductionInitiation = function () {
        var detail = ppState.production.detail;
        var leadProductId = ppState.production.leadProductId;
        var department = selectedProductionDepartment();
        var submitBtn = el('pp-production-submit-btn');

        if (!detail || !leadProductId) {
            toast('Production details are not loaded yet.', 'error');
            return;
        }

        if (!department) {
            toast('Please choose a mapped department.', 'error');
            return;
        }

        if (!department.workflow_mapped) {
            toast('No production workflow mapping is configured for the selected department.', 'error');
            return;
        }

        var productName = (el('pp-production-product-name') || {}).value || '';
        var customFieldErrors = validateProductionCustomFields(detail.ovp_form_schema || []);

        if (!productName.trim()) { toast('Product name is required.', 'error'); return; }
        if (customFieldErrors.length) { toast(customFieldErrors[0], 'error'); return; }

        var formData = new FormData();
        formData.append('department_id', String(department.id));
        formData.append('product_name', productName.trim());
        appendProductionCustomFields(formData, detail.ovp_form_schema || []);

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        }

        apiFormData('/lead-products/' + leadProductId + '/production-initiations', formData)
            .then(function (res) {
                ppState.production.lastSubmission = res;
                ppProductCache = {};
                PP.ppHideModal('pp-modal-production');
                loadDeals();
                toast(res.message || 'Production initiation submitted successfully.');
            })
            .catch(function (err) {
                var msg = firstErrorMessage(err) || err.message || 'Failed to submit production initiation.';
                toast(msg, 'error');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit to Production';
                }
            });
    };

    function handlePaymentModalClosed() {
        ppState.activePayProdId = null;
    }

    function renderProductionBody() {
        var detail = ppState.production.detail;
        var selectedDepartment = selectedProductionDepartment();
        var submitBtn = el('pp-production-submit-btn');

        if (!detail) {
            return;
        }

        if (!detail.departments || !detail.departments.length) {
            setInner('pp-production-body', '<div class="pp-production-empty"><div class="pp-production-empty-title">No departments mapped yet</div><div class="pp-production-empty-copy">This product does not have any department mapping in product master right now. Once departments are assigned there, they will appear here automatically.</div></div>');
            if (submitBtn) submitBtn.classList.add('pp-production-hidden');
            return;
        }

        var statusHtml = '';
        if (ppState.production.lastSubmission && ppState.production.lastSubmission.initiation) {
            var submission = ppState.production.lastSubmission.initiation;
            statusHtml = '<div class="pp-production-status">' +
                '<strong>Production initiated successfully.</strong><br>' +
                'Department: ' + escHtml(submission.department_name) + '<br>' +
                'Product: ' + escHtml(submission.product_name || ((detail.product && detail.product.name) || '')) +
            '</div>';
        }

        var mappingNote = selectedDepartment && !selectedDepartment.workflow_mapped
            ? '<div class="pp-production-empty" style="margin-top:12px"><div class="pp-production-empty-title">Workflow mapping is missing</div><div class="pp-production-empty-copy">Configure the production workflow for this department in Development Production Mapping before submitting this request.</div></div>'
            : '';
        var dynamicFieldsHtml = renderProductionCustomFields(detail.ovp_form_schema || [], detail.latest_initiation);
        var previousCustomDataHtml = renderProductionCustomDataSummary(detail.latest_initiation && detail.latest_initiation.custom_form_data);

        var bodyHtml = statusHtml +
            '<div class="pp-production-card">' +
                '<div class="pp-production-form">' +
                    '<div class="pp-production-form-grid">' +
                        '<div class="ppf-grp">' +
                            '<label class="ppf-lbl">Product Name <span class="ppf-req">*</span></label>' +
                            '<input id="pp-production-product-name" class="ppf-inp ni" type="text" value="' + escAttr((detail.product && detail.product.name) || '') + '" readonly>' +
                        '</div>' +
                        dynamicFieldsHtml +
                    '</div>' +
                '</div>' +
            '</div>' +
            previousCustomDataHtml +
            mappingNote;

        setInner('pp-production-body', bodyHtml);

        if (submitBtn) {
            if (selectedDepartment && selectedDepartment.workflow_mapped) {
                submitBtn.classList.remove('pp-production-hidden');
            } else {
                submitBtn.classList.add('pp-production-hidden');
            }
        }
    }

    function selectedProductionDepartment() {
        var detail = ppState.production.detail;
        var selectedDepartmentId = ppState.production.selectedDepartmentId;

        if (!detail || !Array.isArray(detail.departments)) {
            return null;
        }

        return detail.departments.find(function (department) {
            return Number(department.id) === Number(selectedDepartmentId);
        }) || null;
    }

    function renderProductionCustomFields(fields, latestInitiation) {
        fields = Array.isArray(fields) ? fields : [];

        if (!fields.length) {
            return '';
        }

        var previousValues = {};
        (latestInitiation && Array.isArray(latestInitiation.custom_form_data) ? latestInitiation.custom_form_data : []).forEach(function (entry) {
            if (entry && entry.field_name) {
                previousValues[String(entry.field_name)] = entry.value;
            }
        });

        return '<div class="ppf-grp pp-production-form-full">' +
            '<label class="ppf-lbl"></label>' +
            '<div class="pp-production-dynamic-grid">' +
                fields.map(function (field) {
                    return renderProductionCustomField(field, previousValues[field.field_name], detailLeadDefaults());
                }).join('') +
            '</div>' +
        '</div>';
    }

    function renderProductionCustomField(field, previousValue, leadDefaults) {
        var id = 'pp-custom-' + field.field_name;
        var label = escHtml(field.label || field.field_name);
        var placeholder = escAttr(field.placeholder || '');
        var helpText = field.help_text ? '<div class="pp-production-field-help">' + escHtml(field.help_text) + '</div>' : '';
        var required = field.is_required ? ' <span class="ppf-req">*</span>' : '';
        var value = resolveProductionFieldValue(field, previousValue, leadDefaults);
        var inputHtml = '';

        if (field.field_type === 'textarea') {
            inputHtml = '<textarea id="' + id + '" data-field-type="' + escAttr(field.field_type) + '" data-field-name="' + escAttr(field.field_name) + '" class="ppf-ta pp-production-dynamic-input" placeholder="' + placeholder + '">' + escHtml(value) + '</textarea>';
        } else if (field.field_type === 'select') {
            inputHtml = '<div class="ppf-rel"><select id="' + id + '" data-field-type="' + escAttr(field.field_type) + '" data-field-name="' + escAttr(field.field_name) + '" class="ppf-sel ni pp-production-dynamic-input">' +
                '<option value="">Choose an option</option>' +
                (field.options || []).map(function (option) {
                    var selected = String(value) === String(option.value) ? ' selected' : '';
                    return '<option value="' + escAttr(option.value) + '"' + selected + '>' + escHtml(option.label) + '</option>';
                }).join('') +
                '</select><svg class="ppf-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></div>';
        } else if (field.field_type === 'radio') {
            inputHtml = '<div class="pp-production-choice-group">' +
                (field.options || []).map(function (option, index) {
                    var checked = String(value) === String(option.value) ? ' checked' : '';
                    return '<label class="pp-production-choice"><input type="radio" name="' + escAttr(id) + '" data-field-type="radio" data-field-name="' + escAttr(field.field_name) + '" value="' + escAttr(option.value) + '"' + checked + '> <span>' + escHtml(option.label) + '</span></label>';
                }).join('') +
                '</div>';
        } else if (field.field_type === 'checkbox') {
            var selectedValues = Array.isArray(value) ? value.map(String) : (value ? [String(value)] : []);
            inputHtml = '<div class="pp-production-choice-group">' +
                (field.options || []).map(function (option) {
                    var checked = selectedValues.indexOf(String(option.value)) !== -1 ? ' checked' : '';
                    return '<label class="pp-production-choice"><input type="checkbox" data-field-type="checkbox" data-field-name="' + escAttr(field.field_name) + '" value="' + escAttr(option.value) + '"' + checked + '> <span>' + escHtml(option.label) + '</span></label>';
                }).join('') +
                '</div>';
        } else if (field.field_type === 'file') {
            var previousFile = value && typeof value === 'object' && value.name
                ? '<div class="pp-production-file-note">Last file: <a href="' + escAttr(value.url || '#') + '" target="_blank" rel="noopener">' + escHtml(value.name) + '</a></div>'
                : '';
            inputHtml = '<input id="' + id + '" data-field-type="file" data-field-name="' + escAttr(field.field_name) + '" class="pp-production-file pp-production-dynamic-input" type="file">' + previousFile;
        } else {
            var inputType = field.field_type === 'number' ? 'number' : (field.field_type === 'date' ? 'date' : 'text');
            var minAttr = field.validation_rules && field.validation_rules.min !== undefined ? ' min="' + escAttr(field.validation_rules.min) + '"' : '';
            var maxAttr = field.validation_rules && field.validation_rules.max !== undefined ? ' max="' + escAttr(field.validation_rules.max) + '"' : '';
            var stepAttr = field.field_type === 'number' ? ' step="0.01"' : '';
            inputHtml = '<input id="' + id + '" data-field-type="' + escAttr(field.field_type) + '" data-field-name="' + escAttr(field.field_name) + '" class="ppf-inp ni pp-production-dynamic-input" type="' + inputType + '" placeholder="' + placeholder + '" value="' + escAttr(value) + '"' + minAttr + maxAttr + stepAttr + '>';
        }

        return '<div class="ppf-grp pp-production-dynamic-field">' +
            '<label class="ppf-lbl">' + label + required + '</label>' +
            inputHtml +
            helpText +
        '</div>';
    }

    function detailLeadDefaults() {
        return (ppState.production.detail && ppState.production.detail.lead) || {};
    }

    function resolveProductionFieldValue(field, previousValue, leadDefaults) {
        if (previousValue !== undefined && previousValue !== null && previousValue !== '') {
            return previousValue;
        }

        var mappedLeadValue = mappedLeadValueForField(field, leadDefaults || {});
        if (mappedLeadValue !== '') {
            return mappedLeadValue;
        }

        return field.default_value || '';
    }

    function mappedLeadValueForField(field, leadDefaults) {
        var lookup = normalizedProductionFieldLookup(field);

        if (lookup.indexOf('contact_name') !== -1) {
            return leadDefaults.contact_name || '';
        }

        if (lookup.indexOf('mobile_number') !== -1) {
            return leadDefaults.mobile_number || '';
        }

        if (lookup.indexOf('email') !== -1) {
            return leadDefaults.email || '';
        }

        if (lookup.indexOf('company_name') !== -1) {
            return leadDefaults.company_name || '';
        }

        return '';
    }

    function normalizedProductionFieldLookup(field) {
        var source = [
            field && field.field_name,
            field && field.label,
            field && field.placeholder,
        ].filter(Boolean).join(' ');

        var normalized = String(source || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();

        var matches = [];

        if (/(customer|client|contact|full|cust(omer)?)\s*name/.test(normalized)) {
            matches.push('contact_name');
        }
        if (/(mobile|phone|contact)\s*(number|no)?/.test(normalized) || /\bmobile_number\b/.test(normalized)) {
            matches.push('mobile_number');
        }
        if (/email\s*(id|address)?/.test(normalized) || /\bemail\b/.test(normalized)) {
            matches.push('email');
        }
        if (/company\s*name/.test(normalized) || /\bcompany\b/.test(normalized)) {
            matches.push('company_name');
        }

        return matches;
    }

    function validateProductionCustomFields(fields) {
        var errors = [];

        (Array.isArray(fields) ? fields : []).forEach(function (field) {
            var value = readProductionCustomFieldValue(field);
            if (field.is_required) {
                if (field.field_type === 'file' && !value) {
                    errors.push(field.label + ' is required.');
                    return;
                }
                if (field.field_type === 'checkbox' && (!Array.isArray(value) || !value.length)) {
                    errors.push('Select at least one option for ' + field.label + '.');
                    return;
                }
                if ((value === null || value === undefined || value === '') && field.field_type !== 'file') {
                    errors.push(field.label + ' is required.');
                    return;
                }
            }

            if (field.field_type === 'number' && value !== '' && value !== null && value !== undefined) {
                var num = parseFloat(value);
                if (isNaN(num)) {
                    errors.push(field.label + ' must be a valid number.');
                    return;
                }
                if (field.validation_rules && field.validation_rules.min !== undefined && num < parseFloat(field.validation_rules.min)) {
                    errors.push(field.label + ' must be at least ' + field.validation_rules.min + '.');
                    return;
                }
                if (field.validation_rules && field.validation_rules.max !== undefined && num > parseFloat(field.validation_rules.max)) {
                    errors.push(field.label + ' must be at most ' + field.validation_rules.max + '.');
                }
            }
        });

        return errors;
    }

    function appendProductionCustomFields(formData, fields) {
        (Array.isArray(fields) ? fields : []).forEach(function (field) {
            var value = readProductionCustomFieldValue(field);

            if (field.field_type === 'file') {
                if (value) {
                    formData.append('custom_files[' + field.field_name + ']', value);
                }
                return;
            }

            if (field.field_type === 'checkbox') {
                (Array.isArray(value) ? value : []).forEach(function (item, index) {
                    formData.append('custom_fields[' + field.field_name + '][' + index + ']', item);
                });
                return;
            }

            if (value !== null && value !== undefined && value !== '') {
                formData.append('custom_fields[' + field.field_name + ']', value);
            }
        });
    }

    function readProductionCustomFieldValue(field) {
        var selector = '[data-field-name="' + field.field_name + '"]';

        if (field.field_type === 'radio') {
            var checked = document.querySelector(selector + ':checked');
            return checked ? checked.value : '';
        }

        if (field.field_type === 'checkbox') {
            return Array.prototype.slice.call(document.querySelectorAll(selector + ':checked')).map(function (input) {
                return input.value;
            });
        }

        if (field.field_type === 'file') {
            var fileInput = document.querySelector(selector);
            return fileInput && fileInput.files ? fileInput.files[0] : null;
        }

        var input = document.querySelector(selector);
        return input ? input.value : '';
    }

    function renderProductionCustomDataSummary(entries) {
        entries = Array.isArray(entries) ? entries : [];
        if (!entries.length) {
            return '';
        }

        return '<div class="pp-production-status" style="margin-top:12px;">' +
            '<strong>Previous Customization Data</strong>' +
            entries.map(function (entry) {
                return '<div style="margin-top:6px;"><span style="font-weight:700;">' + escHtml(entry.label || entry.field_name || 'Field') + ':</span> ' + formatProductionCustomValue(entry) + '</div>';
            }).join('') +
        '</div>';
    }

    function formatProductionCustomValue(entry) {
        if (entry.type === 'file' && entry.value && typeof entry.value === 'object') {
            return '<a href="' + escAttr(entry.value.url || '#') + '" target="_blank" rel="noopener">' + escHtml(entry.value.name || 'View file') + '</a>';
        }

        if (Array.isArray(entry.value)) {
            return escHtml(entry.value.join(', '));
        }

        return escHtml(entry.value == null ? '' : String(entry.value));
    }

    function renderHistoryBody(p, overall) {
        var progress  = p.total > 0 ? Math.min(100, Math.round((p.paid / p.total) * 100)) : 0;
        var progColor = progress >= 100 ? '#16a34a' : (progress > 0 ? '#fe5f04' : '#e1dee3');
        var pending   = p.total - p.paid;
        var html      = '';

        // Totals grid
        html += '<div class="pp-hist-totals">' +
            '<div><div class="pp-htl">Total Value</div><div class="pp-htv">'  + fmt(p.total) + '</div></div>' +
            '<div><div class="pp-htl">Collected</div><div class="pp-htv" style="color:#16a34a">' + fmt(p.paid) + '</div></div>' +
            '<div><div class="pp-htl">Pending</div><div class="pp-htv" style="color:' + (pending > 0 ? '#dc2626' : '#16a34a') + '">' + fmt(pending) + '</div></div>' +
        '</div>';

        // Progress bar
        html += '<div class="pp-hist-prog">' +
            '<div class="pp-hist-pbar"><div class="pp-hist-pfill" style="width:' + progress + '%;background:' + progColor + '"></div></div>' +
            '<div class="pp-hist-plabels">' +
                '<span>' + p.payments.length + ' payment(s)</span>' +
                '<span style="font-weight:700;color:' + progColor + '">' + progress + '% collected</span>' +
            '</div></div>';

        // Product payments
        html += '<h4 class="pp-hist-section-title">This Product</h4>';
        if (p.payments.length === 0) {
            html += renderHistEmpty();
        } else {
            var running = 0;
            html += '<div class="pp-hist-list">';
            p.payments.forEach(function (pmt) {
                running += pmt.amount;
                html += renderHistItem(pmt, running);
            });
            html += '</div>';
        }

        // Overall for the lead
        if (overall.length > 0) {
            html += '<h4 class="pp-hist-section-title" style="margin-top:16px">All Payments (This Lead)</h4>';
            html += '<div class="pp-hist-list">';
            var overallRunning = 0;
            overall.forEach(function (pmt) {
                overallRunning += pmt.amount;
                html += renderHistItem(pmt, overallRunning, true);
            });
            html += '</div>';
        }

        return html;
    }

    function renderHistItem(pmt, running, isOverall) {
        var attUrl = (pmt.attachment && pmt.attachment.url) || pmt.attachment_url || null;
        var attName = (pmt.attachment && pmt.attachment.name) || pmt.attachment_name || '';

        var attHtml = '';
        if (attUrl) {
            attHtml = '<div class="pp-hist-ref">Attachment: ' +
                '<a href="' + escAttr(attUrl) + '" target="_blank" rel="noopener">' + escHtml(attName || 'View') + '</a>' +
                ' <a href="' + escAttr(attUrl) + '" download="' + escAttr(attName || '') + '" class="pp-hist-download" title="Download">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>' +
                        '<polyline points="7 10 12 15 17 10"/>' +
                        '<line x1="12" y1="15" x2="12" y2="3"/>' +
                    '</svg>' +
                '</a>' +
            '</div>';
        }

        return '<div class="pp-hist-item">' +
            '<div class="pp-hist-mode-wrap" style="background:' + pmt.modeColor + '20">' + pmt.modeIcon + '</div>' +
            '<div class="pp-hist-info">' +
                '<div class="pp-hist-mname">' + pmt.modeLabel + '</div>' +
                '<div class="pp-hist-date">'  + pmt.date + ' · By ' + escHtml(pmt.by) + '</div>' +
                (pmt.ref   ? '<div class="pp-hist-ref">Ref: ' + escHtml(pmt.ref)   + '</div>' : '') +
                (pmt.notes ? '<div class="pp-hist-note">'     + escHtml(pmt.notes) + '</div>' : '') +
                attHtml +
            '</div>' +
            '<div class="pp-hist-right">' +
                '<div class="pp-hist-amt">' + fmt(pmt.amount) + '</div>' +
                '<div class="pp-hist-run">Cumulative: ' + fmt(running) + '</div>' +
            '</div>' +
            (!isOverall ?
                '<button type="button" class="pp-hist-del" onclick="PP.ppDeletePayment(' + pmt.id + ')" title="Remove">' +
                    '<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>' +
                '</button>' : '') +
        '</div>';
    }

    function renderHistEmpty() {
        return '<div class="pp-hist-empty">' +
            '<div class="pp-hist-empty-ico">💸</div>' +
            '<div style="font-size:14px;font-weight:700;color:#7c7c7c">No payments recorded yet</div>' +
            '<div style="font-size:12px;color:#9e9e9e;margin-top:4px">Use "Add Payment" below.</div>' +
        '</div>';
    }

    /* ─────────────────────────────────────────────────────────────
       DELETE HELPERS
    ───────────────────────────────────────────────────────────── */
    PP.ppDeleteProduct = function (id, name) {
        if (!confirm('Remove "' + name + '" from this lead?')) return;
        api('DELETE', '/lead-products/' + id)
        .then(function () {
            toast('Product removed.'); ppProductCache = {}; loadDeals();
        })
        .catch(function () { toast('Failed to remove product.', 'error'); });
    };

    PP.ppDeletePayment = function (id) {
        if (!confirm('Remove this payment?')) return;
        api('DELETE', '/payments/' + id)
        .then(function () {
            toast('Payment removed.'); ppProductCache = {}; loadDeals();
            PP.ppHideModal('pp-modal-history');
        })
        .catch(function () { toast('Failed to remove payment.', 'error'); });
    };

    /* ─────────────────────────────────────────────────────────────
       MODE TILE PICKER  (payment form)
    ───────────────────────────────────────────────────────────── */
    PP.ppPickMode = function (tile) {
        qsa('.ppf-mode-tile').forEach(function (t) { t.classList.remove('pp-sel'); });
        tile.classList.add('pp-sel');
        var inp = el('pp-mode-val');
        if (inp) inp.value = tile.dataset.val;
    };

    /* ─────────────────────────────────────────────────────────────
       STATUS CONFIG  (colors for dynamic lead statuses)
    ───────────────────────────────────────────────────────────── */
    var DEFAULT_STATUS_STYLE = { icon:'', bg:'#f5f4f6', text:'#7c7c7c', border:'#e1dee3' };
    var STATUS_STYLES_BY_KEY = {
        'new'      : { icon:'', bg:'#eff6ff', text:'#1d4ed8', border:'#bfdbfe' },
        'hot'      : { icon:'', bg:'#fff7ed', text:'#c2410c', border:'#fed7aa' },
        'warm'     : { icon:'', bg:'#fefce8', text:'#a16207', border:'#fde68a' },
        'cold'     : { icon:'', bg:'#f0f9ff', text:'#0369a1', border:'#bae6fd' },
        'converted': { icon:'', bg:'#f0fdf4', text:'#15803d', border:'#bbf7d0' },
        'won'      : { icon:'', bg:'#f0fdf4', text:'#15803d', border:'#bbf7d0' },
        'lost'     : { icon:'', bg:'#fef2f2', text:'#b91c1c', border:'#fecaca' },
    };

    var STATUS_CONFIG = {};

    /* ─────────────────────────────────────────────────────────────
       HELPERS
    ───────────────────────────────────────────────────────────── */
    STATUS_CONFIG = buildStatusConfig(STATUS_OPTIONS);

    function setInner(id, html) {
        var e = el(id); if (e) e.innerHTML = html;
    }

    function todayStr() {
        return new Date().toISOString().split('T')[0];
    }

    function normalizePrice(value) {
        return parseFloat(parseFloat(value || 0).toFixed(2));
    }

    function hasPriceChange(products) {
        return products.some(function (p) {
            return normalizePrice(p.price) !== normalizePrice(p.originalPrice);
        });
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function escAttr(str) {
        return String(str || '').replace(/'/g,'&#39;').replace(/"/g,'&quot;');
    }

    function firstErrorMessage(err) {
        if (!err || !err.errors) {
            return '';
        }

        var keys = Object.keys(err.errors);
        if (!keys.length) {
            return '';
        }

        var first = err.errors[keys[0]];
        return Array.isArray(first) && first.length ? first[0] : '';
    }

    function normalizeStatusOptions(raw) {
        var options = (raw || []).map(function (status) {
            var id = status && (status.id !== undefined ? status.id : status.value);
            var name = status && (status.name !== undefined ? status.name : status.label);
            if (id === undefined || id === null || id === '') id = name;
            if (name === undefined || name === null || name === '') name = id;

            return {
                id: String(id || ''),
                name: String(name || ''),
            };
        }).filter(function (status) {
            return status.id && status.name;
        });

        return options;
    }

    function buildStatusConfig(options) {
        var map = {};
        normalizeStatusOptions(options).forEach(function (option) {
            var style = styleForStatusName(option.name);
            map[String(option.id)] = style;
            map[statusKey(option.name)] = style;
        });
        return map;
    }

    function getStatusOption(value) {
        value = String(value || '');
        return STATUS_OPTIONS.find(function (option) {
            return String(option.id) === value;
        });
    }

    function getStatusOptionByKey(key) {
        key = statusKey(key);
        return STATUS_OPTIONS.find(function (option) {
            return statusKey(option.name) === key;
        }) || null;
    }

    function getStatusOptions(selectedValue, selectedLabel) {
        selectedValue = String(selectedValue || '');
        var options = STATUS_OPTIONS.slice();
        var hasSelected = !selectedValue || options.some(function (option) {
            return String(option.id) === selectedValue;
        });

        if (!hasSelected) {
            options.unshift({
                id: selectedValue,
                name: selectedLabel || selectedValue,
            });
        }

        return options;
    }

    function getStatusConfig(value, label) {
        return STATUS_CONFIG[String(value || '')] || styleForStatusName(label || value);
    }

    function styleForStatusName(name) {
        return STATUS_STYLES_BY_KEY[statusKey(name)] || DEFAULT_STATUS_STYLE;
    }

    function statusKey(name) {
        return String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    function optionText(icon, label) {
        return escHtml((icon ? icon + ' ' : '') + (label || 'Status'));
    }

    /* ─────────────────────────────────────────────────────────────
       BOOT — run once on load
    ───────────────────────────────────────────────────────────── */
    function boot() {
        if (!LEAD_ID) return;
        STATUS_CONFIG = buildStatusConfig(STATUS_OPTIONS);
        bindDealNameInput();
        loadDeals();
    }

    function bindDealNameInput() {
        var dealInp = el('pp-deal-name');
        if (!dealInp || dealInp.dataset.bound === '1') {
            return;
        }

        dealInp.dataset.bound = '1';
        dealInp.addEventListener('input', function () {
            var currentValue = dealInp.value.trim();
            var autoSuggested = dealInp.dataset.autoSuggested || '';
            ppState.dealNameTouched = currentValue !== '' && currentValue !== autoSuggested;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Expose namespace
    window.PP = PP;

}(window.PP || {}));
