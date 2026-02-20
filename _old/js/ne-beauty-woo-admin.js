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
       SEARCH indicator
    ========================= */
    const $searchInput = $('#nebf-live-search');

    // Create indicator if it does not exist
    if (!$('#nebf-search-indicator').length) {
        $('<span id="nebf-search-indicator" style="margin-left:8px; display:none; font-style:italic;">Söker…</span>')
            .insertAfter($searchInput);
    }
    const $indicator = $('#nebf-search-indicator');

    /* =========================
       ACCORDION
    ========================= */
    $(document).on('click', 'tr.product-row', function (e) {
        // Ignore clicks on checkbox or label
        if ($(e.target).is('input[type="checkbox"], label')) return;

        const $row = $(this);
        const accId = $row.data('accordion');
        const $accordion = $('#' + accId);
        if (!$accordion.length) return;

        // Close all other accordion rows and remove 'is-open' from them
        $('tr.accordion-row').not($accordion).hide();
        $('tr.product-row').not($row).removeClass('is-open');

        // Toggle the clicked row
        $accordion.toggle();
        $row.toggleClass('is-open');
    });

    /* =========================
       SELECT ALL / DESELECT ALL
    ========================= */
    const $selectAllBtn = $('#nebf-select-all');

    $(document).on('click', '#nebf-select-all', function (e) {
        e.preventDefault();

        const $checkboxes = $('tr.product-row input[type="checkbox"]');

        // If at least one is unchecked, check all; otherwise uncheck all
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;

        if (allChecked) {
            $checkboxes.prop('checked', false).trigger('change');
            $selectAllBtn.text('Välj alla');
        } else {
            $checkboxes.prop('checked', true).trigger('change');
            $selectAllBtn.text('Avmarkera alla');
        }
    });

    /* =========================
       LIVE SEARCH (3+ characters)
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

            // Close details if the main row is hidden
            if (!match && accId) {
                $('#' + accId).hide();
            }
        });

        $indicator.hide();
    }, 300);

    $(document).on('keyup', '#nebf-live-search', function () {
        if (this.value.length >= 3) {
            $indicator.show();
        } else {
            $indicator.hide();
        }
        handleLiveSearch();
    });

    /* =========================
       HIGHLIGHT SELECTED ROW
    ========================= */
    $(document).on('change', 'tr.product-row input[type="checkbox"]', function () {
        const $row = $(this).closest('tr.product-row');
        $row.toggleClass('is-selected', this.checked);

        // Update button text dynamically
        const $checkboxes = $('tr.product-row input[type="checkbox"]');
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $selectAllBtn.text(allChecked ? 'Avmarkera alla' : 'Välj alla');
    });

    // If the page loads with pre-selected checkboxes
    $('tr.product-row input[type="checkbox"]:checked').each(function () {
        $(this).closest('tr.product-row').addClass('is-selected');
    });

});
