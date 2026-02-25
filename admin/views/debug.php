<?php if (!defined('ABSPATH')) exit; ?>

<style>
.nebf-debug-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 6px;
}

.nebf-debug-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.nebf-debug-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.nebf-success { background:#d4edda; color:#155724; }
.nebf-error   { background:#f8d7da; color:#721c24; }
.nebf-empty   { background:#e2e3e5; color:#383d41; }

.nebf-debug-content {
    margin-top:15px;
    display:none;
}

.nebf-debug-pre {
    background:#111;
    color:#0f0;
    padding:15px;
    max-height:400px;
    overflow:auto;
    font-size:12px;
}
</style>

<div class="wrap">
    <h2><?php esc_html_e('BeautyFort Debug', 'nebf-mvc'); ?></h2>

    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('nebf_clear_debug_action'); ?>
        <button name="nebf_clear_debug" class="button button-secondary">
            <?php esc_html_e('Clear Logs', 'nebf-mvc'); ?>
        </button>
    </form>

    <?php
    $sections = [
        'Create Order'   => $create_trace,
        'Add Order Item' => $add_trace,
        'Edit Order Item'=> $edit_trace,
        'Last Error'     => $error_trace,
    ];

    foreach ($sections as $title => $data):

        $statusClass = 'nebf-empty';
        $statusText  = 'Empty';

        if (is_array($data) && !empty($data)) {
            if (!empty($data['parsed']['success']) || !empty($data['success'])) {
                $statusClass = 'nebf-success';
                $statusText  = 'Success';
            } elseif (!empty($data['error']) || !empty($data['parsed']['errors'])) {
                $statusClass = 'nebf-error';
                $statusText  = 'Error';
            }
        }
    ?>

    <div class="nebf-debug-card">
        <div class="nebf-debug-header">
            <strong><?php echo esc_html($title); ?></strong>
            <span class="nebf-debug-badge <?php echo esc_attr($statusClass); ?>">
                <?php echo esc_html($statusText); ?>
            </span>
        </div>

        <div class="nebf-debug-content">
            <pre class="nebf-debug-pre"><?php echo esc_html(print_r($data, true)); ?></pre>

            <?php if (!empty($data)): ?>
                <form method="post">
                    <input type="hidden" name="nebf_download_json" value="<?php echo esc_attr(base64_encode(wp_json_encode($data))); ?>">
                    <button class="button button-small">
                        <?php esc_html_e('Download JSON', 'nebf-mvc'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.nebf-debug-header').forEach(function(header) {
    header.addEventListener('click', function() {
        var content = this.parentElement.querySelector('.nebf-debug-content');
        content.style.display = content.style.display === 'block' ? 'none' : 'block';
    });
});
</script>