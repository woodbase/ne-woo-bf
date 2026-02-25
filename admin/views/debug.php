<?php if (!defined('ABSPATH')) exit; ?>

<style>
.nebf-debug-card {
    background:#fff;
    border:1px solid #ccd0d4;
    padding:20px;
    margin-bottom:20px;
    border-radius:6px;
}

.nebf-debug-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
}

.nebf-badge {
    padding:4px 10px;
    border-radius:12px;
    font-size:12px;
    font-weight:600;
}

.nebf-success { background:#d4edda;color:#155724; }
.nebf-error   { background:#f8d7da;color:#721c24; }
.nebf-empty   { background:#e2e3e5;color:#383d41; }

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

.nebf-debug-actions {
    margin-top:10px;
}
</style>

<div class="wrap">
    <h2><?php esc_html_e('BeautyFort Debug', 'nebf-mvc'); ?></h2>

    <?php if ($testmode): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('TestMode is enabled – page auto-refreshes every 10 seconds.', 'nebf-mvc'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('nebf_clear_debug_action'); ?>
        <button name="nebf_clear_debug" class="button button-secondary">
            <?php esc_html_e('Clear Logs', 'nebf-mvc'); ?>
        </button>
    </form>

    <?php
    $sections = [
        'Create Order'   => $traces['create'] ?? [],
        'Add Order Item' => $traces['add'] ?? [],
        'Edit Order Item'=> $traces['edit'] ?? [],
        'Last Error'     => $traces['error'] ?? [],
    ];

    foreach ($sections as $title => $data):

        $statusClass = 'nebf-empty';
        $statusText  = __('Empty', 'nebf-mvc');

        if (!empty($data)) {
            if (!empty($data['parsed']['success']) || !empty($data['success'])) {
                $statusClass = 'nebf-success';
                $statusText  = __('Success', 'nebf-mvc');
            } elseif (!empty($data['error']) || !empty($data['parsed']['errors'])) {
                $statusClass = 'nebf-error';
                $statusText  = __('Error', 'nebf-mvc');
            }
        }

        $httpCode = $data['http_code'] ?? null;
    ?>

    <div class="nebf-debug-card">
        <div class="nebf-debug-header">
            <strong><?php echo esc_html($title); ?></strong>

            <div>
                <?php if ($httpCode): ?>
                    <span class="nebf-badge"><?php echo esc_html('HTTP ' . $httpCode); ?></span>
                <?php endif; ?>

                <span class="nebf-badge <?php echo esc_attr($statusClass); ?>">
                    <?php echo esc_html($statusText); ?>
                </span>
            </div>
        </div>

        <div class="nebf-debug-content">

            <pre class="nebf-debug-pre" id="pre-<?php echo md5($title); ?>">
<?php echo esc_html(print_r($data, true)); ?>
            </pre>

            <?php if (!empty($data)): ?>
                <div class="nebf-debug-actions">
                    <button class="button button-small nebf-copy-btn"
                        data-target="pre-<?php echo md5($title); ?>">
                        <?php esc_html_e('Copy', 'nebf-mvc'); ?>
                    </button>

                    <a class="button button-small"
                       href="data:application/json;charset=utf-8,<?php echo rawurlencode(wp_json_encode($data)); ?>"
                       download="<?php echo sanitize_title($title); ?>.json">
                        <?php esc_html_e('Download JSON', 'nebf-mvc'); ?>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php endforeach; ?>
</div>

<script>
// Toggle collapse
document.querySelectorAll('.nebf-debug-header').forEach(function(header){
    header.addEventListener('click', function(){
        var content = this.parentElement.querySelector('.nebf-debug-content');
        content.style.display = content.style.display === 'block' ? 'none' : 'block';
    });
});

// Copy
document.querySelectorAll('.nebf-copy-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var target = document.getElementById(this.dataset.target);
        navigator.clipboard.writeText(target.innerText);
    });
});

// Auto refresh in testmode
<?php if ($testmode): ?>
setTimeout(function(){
    location.reload();
}, 10000);
<?php endif; ?>
</script>