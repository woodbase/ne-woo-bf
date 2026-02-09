jQuery(function ($) {
  $('.nebf-products-table').on('click', '.product-row', function (e) {

        // Klick på checkbox ska inte toggla
        if ($(e.target).is('input')) {
            return;
        }

        const accordionId = $(this).data('accordion');
        const $row = $('#' + accordionId);

        $row.toggle();
    });

    /* =========================
       ACCORDION (rad → detaljer)
       ========================= */
    $('.nebf-row').on('click', function (e) {
        if ($(e.target).is('input[type="checkbox"]')) return;

        const accId = $(this).data('accordion');
        $('#' + accId).toggle();
    });

    /* =========================
       VÄLJ ALLA (ej importerade)
       ========================= */
    $('#nebf-select-all').on('click', function (e) {
        e.preventDefault();

        $('input[name="import_ids[]"]').prop('checked', true);
    });

    /* =========================
       LIVE-SÖK (client side)
       ========================= */
    $('#nebf-live-search').on('keyup', function () {
        const value = $(this).val().toLowerCase();

        $('#nebf-products-table tbody tr.nebf-row').each(function () {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(value) !== -1);
        });
    });

});
