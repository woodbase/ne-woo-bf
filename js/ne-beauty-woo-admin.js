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

});