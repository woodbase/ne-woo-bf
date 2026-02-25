<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php esc_html_e('BeautyFort Debug Console', 'nebf-mvc'); ?></h1>

    <?php if ($testmode): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('TestMode enabled – auto refresh every 10 seconds.', 'nebf-mvc'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('nebf_clear_debug_action'); ?>
        <button name="nebf_clear_debug" class="button button-secondary">
            <?php esc_html_e('Clear Logs', 'nebf-mvc'); ?>
        </button>
    </form>

    <div class="nebf-debug-wrapper">

    <?php
    $sections = [
        'Create Order'    => $traces['create'] ?? [],
        'Add Order Item'  => $traces['add'] ?? [],
        'Edit Order Item' => $traces['edit'] ?? [],
        'Last Error'      => $traces['error'] ?? [],
    ];

    $autoOpenId = null;

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
                $autoOpenId = md5($title); // auto open latest error
            }
        }

        $httpCode = $data['http_code'] ?? null;
        $sectionId = md5($title);
    ?>

    <div class="nebf-debug-card" id="card-<?php echo esc_attr($sectionId); ?>">

        <div class="nebf-debug-header" data-target="<?php echo esc_attr($sectionId); ?>">
            <div class="nebf-debug-title"><?php echo esc_html($title); ?></div>

            <div class="nebf-debug-badges">

                <?php if ($httpCode): ?>
                    <span class="nebf-badge nebf-http">
                        <?php echo esc_html('HTTP ' . $httpCode); ?>
                    </span>
                <?php endif; ?>

                <span class="nebf-badge <?php echo esc_attr($statusClass); ?>">
                    <?php echo esc_html($statusText); ?>
                </span>

            </div>
        </div>

        <?php if (!empty($data['parsed']['errors'])): ?>
            <div class="nebf-debug-summary">
                <strong><?php esc_html_e('API Errors:', 'nebf-mvc'); ?></strong><br>
                <?php foreach ($data['parsed']['errors'] as $err): ?>
                    <?php echo esc_html($err['description'] ?? ''); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="nebf-debug-content" id="<?php echo esc_attr($sectionId); ?>">

            <?php if (!empty($data)): ?>

                <pre class="nebf-debug-pre" id="pre-<?php echo esc_attr($sectionId); ?>">
<?php echo esc_html(print_r($data, true)); ?>
                </pre>

                <div class="nebf-debug-actions">
                    <button class="button button-small nebf-copy-btn"
                        data-target="pre-<?php echo esc_attr($sectionId); ?>">
                        <?php esc_html_e('Copy', 'nebf-mvc'); ?>
                    </button>

                    <a class="button button-small"
                       href="data:application/json;charset=utf-8,<?php echo rawurlencode(wp_json_encode($data)); ?>"
                       download="<?php echo sanitize_title($title); ?>.json">
                        <?php esc_html_e('Download JSON', 'nebf-mvc'); ?>
                    </a>
                </div>

            <?php else: ?>
                <div class="nebf-debug-empty">
                    <?php esc_html_e('No data logged yet.', 'nebf-mvc'); ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php endforeach; ?>

    </div>
</div>

<script>
// Toggle collapse
document.querySelectorAll('.nebf-debug-header').forEach(function(header){
    header.addEventListener('click', function(){
        var id = this.dataset.target;
        var content = document.getElementById(id);
        content.style.display = content.style.display === 'block' ? 'none' : 'block';
    });
});

// Copy to clipboard
document.querySelectorAll('.nebf-copy-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var target = document.getElementById(this.dataset.target);
        navigator.clipboard.writeText(target.innerText);
    });
});

// Auto open latest error
<?php if ($autoOpenId): ?>
document.getElementById('<?php echo $autoOpenId; ?>').style.display = 'block';
<?php endif; ?>

// Auto refresh in testmode
<?php if ($testmode): ?>
setTimeout(function(){
    location.reload();
}, 10000);
<?php endif; ?>
</script>