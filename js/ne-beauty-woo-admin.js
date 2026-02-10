jQuery(function ($) {

    /* =========================
       DEBOUNCE helper
       ========================= */
    function nebfDebounce(fn, delay) {
        let timer = null;
        return function () {
            const context = this;
            const args = arguments;

            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    /* =========================
       SÖKER-indikator
       ========================= */
    const $searchInput = $('#nebf-live-search');

    // Skapa indikator om den inte finns
    if (!$('#nebf-search-indicator').length) {
        $('<span id="nebf-search-indicator" style="margin-left:8px; display:none; font-style:italic;">Söker…</span>')
            .insertAfter($searchInput);
    }

    const $indicator = $('#nebf-search-indicator');

    /* =========================
       ACCORDION
       ========================= */
    $(document).on('click', 'tr.product-row', function (e) {

        if ($(e.target).is('input[type="checkbox"], label')) return;

        const accId = $(this).data('accordion');
        if (!accId) return;

        $('tr.accordion-row').not('#' + accId).hide();
        $('#' + accId).toggle();
    });

    /* =========================
       VÄLJ ALLA
       ========================= */
    $(document).on('click', '#nebf-select-all', function (e) {
        e.preventDefault();
        $('tr.product-row input[type="checkbox"]').prop('checked', true);
    });

    /* =========================
       LIVE-SÖK (3+ tecken)
       ========================= */
    const handleLiveSearch = nebfDebounce(function () {

        const value = $searchInput.val().toLowerCase();
        const doSearch = value.length >= 3;

        $('tr.product-row').each(function () {

            const $row = $(this);
            const text = $row.text().toLowerCase();
            const accId = $row.data('accordion');
            const match = text.indexOf(value) !== -1;

            if (!doSearch) {
                $row.show();
                return;
            }

            $row.toggle(match);

            // Stäng detaljer om huvudraden döljs
            if (!match && accId) {
                $('#' + accId).hide();
            }
        });

        // Klar → göm indikator
        $indicator.hide();

    }, 300);

    /* =========================
       INPUT event
       ========================= */
    $(document).on('keyup', '#nebf-live-search', function () {

        if (this.value.length >= 3) {
            $indicator.show();
        } else {
            $indicator.hide();
        }

        handleLiveSearch();
    });

    /* =========================
       MARKERA VALD RAD
       ========================= */
    $(document).on('change', 'tr.product-row input[type="checkbox"]', function () {
        const $row = $(this).closest('tr.product-row');
        $row.toggleClass('is-selected', this.checked);
    });

    /* Om sidan laddas med redan valda */
    $('tr.product-row input[type="checkbox"]:checked').each(function () {
        $(this).closest('tr.product-row').addClass('is-selected');
    });
});
