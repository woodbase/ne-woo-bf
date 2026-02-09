jQuery(function ($) {

    /* =========================
       ACCORDION (klick på huvudrad → visa/dölj detaljer)
       ========================= */
    $(document).on('click', 'tr.product-row', function (e) {

        // Ignorera klick på checkbox eller label
        if ($(e.target).is('input[type="checkbox"], label')) return;

        const accId = $(this).data('accordion');
        if (!accId) return;

        // Göm alla andra accordion-rader
        $('tr.accordion-row').not('#' + accId).hide();

        // Toggle den klickade accordion-raden
        $('#' + accId).toggle();
    });

    /* =========================
       VÄLJ ALLA (ej importerade)
       ========================= */
    $(document).on('click', '#nebf-select-all', function (e) {
        e.preventDefault();
        $('tr.product-row input[type="checkbox"]').prop('checked', true);
    });

    /* =========================
       LIVE-SÖK (på huvudrader, påverkar ej öppna detaljer)
       ========================= */
    $(document).on('keyup', '#nebf-live-search', function () {
        const value = $(this).val().toLowerCase();

        $('tr.product-row').each(function () {
            const text = $(this).text().toLowerCase();
            const accId = $(this).data('accordion');
            const visible = text.indexOf(value) !== -1;

            // Toggle huvudrad
            $(this).toggle(visible);

            // Om huvudrad döljs → göm även dess accordion
            if (accId && !visible) {
                $('#' + accId).hide();
            }
        });
    });

});
