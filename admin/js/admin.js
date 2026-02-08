<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nebf-row').forEach(function (row) {
        row.addEventListener('click', function () {
            const detailRow = row.nextElementSibling;

            if (!detailRow || !detailRow.classList.contains('nebf-detail-row')) {
                return;
            }

            const isOpen = detailRow.style.display === 'table-row';

            // Stäng alla andra
            document.querySelectorAll('.nebf-detail-row').forEach(r => {
                r.style.display = 'none';
            });

            // Toggle denna
            detailRow.style.display = isOpen ? 'none' : 'table-row';
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nebf-product-row').forEach(row => {
        row.addEventListener('click', () => {
            const details = row.nextElementSibling;
            details.style.display =
                details.style.display === 'none' ? 'table-row' : 'none';
        });
    });
});

</script>
