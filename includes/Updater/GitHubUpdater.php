<?php

namespace NEBF\Updater;

if (!defined('ABSPATH')) exit;

class GitHubUpdater
{
    private string $owner = 'woodbase';
    private string $repo  = 'ne-woo-bf';
    private string $plugin_file;

    public function __construct(string $plugin_file)
    {
        $this->plugin_file = $plugin_file;

        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
        add_filter('upgrader_source_selection', [$this, 'fixPluginFolder'], 10, 3);

        add_action('admin_notices', [$this, 'adminNotice']);
        add_action('admin_post_nebf_ignore_version', [$this, 'ignoreVersion']);
    }

    /* --------------------------------------------------
       GITHUB FETCH (CACHED)
    -------------------------------------------------- */

    private function getLatestRelease(): ?array
    {
        $cached = get_transient('nebf_github_release');
        if ($cached) return $cached;

        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases";

        $response = wp_remote_get($url, [
            'headers' => [
                'User-Agent' => 'NE Woo BF Plugin'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) return null;

        $releases = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($releases)) return null;

        $ignored = get_option('nebf_ignored_version');

        foreach ($releases as $release) {

            if ($release['draft']) continue;

            $version = ltrim($release['tag_name'], 'v');

            if ($version === $ignored) continue;

            $data = [
                'version'    => $version,
                'prerelease' => $release['prerelease'],
                'zip'        => $release['zipball_url'],
                'url'        => $release['html_url'],
                'body'       => $release['body'] ?? '',
            ];

            set_transient('nebf_github_release', $data, 6 * HOUR_IN_SECONDS);

            return $data;
        }

        return null;
    }

    /* --------------------------------------------------
       NATIVE UPDATE INJECTION
    -------------------------------------------------- */

    public function checkForUpdate($transient)
    {
        if (empty($transient->checked)) return $transient;

        $release = $this->getLatestRelease();
        if (!$release) return $transient;

        if (version_compare($release['version'], NEBF_VERSION, '>')) {

            $plugin_slug = plugin_basename($this->plugin_file);

            $transient->response[$plugin_slug] = (object)[
                'slug'        => dirname($plugin_slug),
                'plugin'      => $plugin_slug,
                'new_version' => $release['version'],
                'package'     => $release['zip'],
                'url'         => $release['url'],
                'tested'      => get_bloginfo('version'),
            ];
        }

        return $transient;
    }

    /* --------------------------------------------------
       PLUGIN DETAILS POPUP
    -------------------------------------------------- */

    public function pluginInfo($result, $action, $args)
    {
        if ($action !== 'plugin_information') return $result;

        if ($args->slug !== dirname(plugin_basename($this->plugin_file))) {
            return $result;
        }

        $release = $this->getLatestRelease();
        if (!$release) return $result;

        return (object)[
            'name'          => 'NE Woo BeautyFort',
            'slug'          => $args->slug,
            'version'       => $release['version'],
            'author'        => 'Woodbase',
            'homepage'      => $release['url'],
            'download_link' => $release['zip'],
            'sections'      => [
                'description' => 'GitHub managed updates.',
                'changelog'   => nl2br(esc_html($release['body']))
            ]
        ];
    }

    /* --------------------------------------------------
       FIX GITHUB ZIP FOLDER NAME
    -------------------------------------------------- */

    public function fixPluginFolder($source, $remote_source, $upgrader)
    {
        global $wp_filesystem;

        $correct = trailingslashit($remote_source) . dirname(plugin_basename($this->plugin_file));

        if ($wp_filesystem->exists($correct)) {
            return $correct;
        }

        $folders = glob($remote_source . '*', GLOB_ONLYDIR);

        if (!empty($folders)) {
            $wp_filesystem->move($folders[0], $correct);
            return $correct;
        }

        return $source;
    }

    /* --------------------------------------------------
       ADMIN NOTICE + IGNORE
    -------------------------------------------------- */

    public function adminNotice()
    {
        $release = $this->getLatestRelease();
        if (!$release) return;

        if (version_compare($release['version'], NEBF_VERSION, '<=')) {
            return;
        }

        $type = $release['prerelease']
            ? '<span style="color:#2271b1;">Release Candidate</span>'
            : '<span style="color:#008a20;">Stable Release</span>';

        $ignore_url = wp_nonce_url(
            admin_url('admin-post.php?action=nebf_ignore_version&version=' . $release['version']),
            'nebf_ignore_version'
        );

        echo '<div class="notice notice-warning">';
        echo '<p><strong>NE Woo BF:</strong> ';
        echo "New {$type} available (v{$release['version']}). ";
        echo "<a href='{$release['url']}' target='_blank'>View release</a> | ";
        echo "<a href='{$ignore_url}'>Ignore this version</a>";
        echo '</p>';
        echo '</div>';

        // Bonus: Reset ignore when stable release arrives
        if (!$release['prerelease']) {
            delete_option('nebf_ignored_version');
        }
    }

    public function ignoreVersion()
    {
        if (!current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('nebf_ignore_version');

        $version = sanitize_text_field($_GET['version'] ?? '');

        if ($version) {
            update_option('nebf_ignored_version', $version);
            delete_transient('nebf_github_release');
        }

        wp_redirect(admin_url('plugins.php'));
        exit;
    }
}