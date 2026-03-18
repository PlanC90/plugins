<?php
/**
 * OmniXEP WooCommerce – GitHub update checker
 * Checks https://github.com/PlanC90/omnixep-woocommerce once per day and offers updates from there.
 */

defined('ABSPATH') || exit;

class OmniXEP_GitHub_Plugin_Updater {

    const GITHUB_REPO_USER = 'PlanC90';
    const GITHUB_REPO_NAME = 'plugins';
    const CACHE_TRANSIENT = 'omnixep_github_plugin_release';
    const CACHE_DURATION = DAY_IN_SECONDS; // 24 hours

    protected $plugin_file;
    protected $plugin_slug;
    protected $current_version;
    protected $github_tags_url;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->current_version = $this->get_plugin_version();
        $this->github_tags_url = 'https://api.github.com/repos/' . self::GITHUB_REPO_USER . '/' . self::GITHUB_REPO_NAME . '/tags';

        add_filter('pre_set_site_transient_update_plugins', array($this, 'inject_update'));
        add_filter('upgrader_source_selection', array($this, 'rename_github_folder'), 10, 4);
    }

    protected function get_plugin_version() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data($this->plugin_file, false, false);
        return isset($data['Version']) ? $data['Version'] : '0';
    }

    /**
     * Get latest release from GitHub (tag name + zipball_url). Cached 24h.
     *
     * @param bool $force Bypass cache.
     * @return object|null { tag_name, zipball_url } or null.
     */
    public function get_latest_release($force = false) {
        if (!$force) {
            $cached = get_transient(self::CACHE_TRANSIENT);
            if ($cached !== false && is_object($cached)) {
                return $cached;
            }
        }

        $args = array(
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
                'Accept'     => 'application/vnd.github.v3+json',
            ),
            'timeout' => 15,
        );

        $response = wp_remote_get($this->github_tags_url, $args);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200 || empty($body)) {
            return null;
        }

        $tags = json_decode($body);
        if (!is_array($tags) || empty($tags)) {
            return null;
        }

        // GitHub tags endpoint returns "most recently created tags" first, but in a monorepo
        // the newest tag might not be a plugin release tag. We pick the highest semver tag.
        $best_tag = null;
        $best_version = null;
        foreach ($tags as $t) {
            if (!is_object($t) || empty($t->name)) continue;
            $name = trim((string) $t->name);
            if (!preg_match('/^v?\d+\.\d+\.\d+$/', $name)) {
                continue;
            }
            $ver = ltrim($name, 'v');
            if ($best_version === null || version_compare($ver, $best_version, '>')) {
                $best_version = $ver;
                $best_tag = $t;
            }
        }

        // Fallback: if no semver tags exist, use the first tag (legacy behavior)
        if ($best_tag === null) {
            if (!isset($tags[0]->name)) {
                return null;
            }
            $best_tag = $tags[0];
        }

        $latest = $best_tag;
        $release = (object) array(
            'tag_name'    => $latest->name,
            'zipball_url' => isset($latest->zipball_url) ? $latest->zipball_url : 'https://api.github.com/repos/' . self::GITHUB_REPO_USER . '/' . self::GITHUB_REPO_NAME . '/zipball/' . $latest->name,
        );

        set_transient(self::CACHE_TRANSIENT, $release, self::CACHE_DURATION);
        return $release;
    }

    /**
     * Inject our plugin into update_plugins transient when GitHub has a newer version.
     */
    public function inject_update($transient) {
        if (empty($transient) || !is_object($transient)) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if (!$release || !isset($release->tag_name)) {
            return $transient;
        }

        $latest_version = ltrim($release->tag_name, 'v');
        
        $package = $release->zipball_url;
        if (strpos($package, 'api.github.com') !== false && !preg_match('/\?access_token=/', $package)) {
            $package = 'https://github.com/' . self::GITHUB_REPO_USER . '/' . self::GITHUB_REPO_NAME . '/archive/refs/tags/' . $release->tag_name . '.zip';
        }

        $item = (object) array(
            'id'            => 'omnixep-woocommerce/omnixep-woocommerce.php',
            'slug'          => 'omnixep-woocommerce',
            'plugin'        => $this->plugin_slug,
            'new_version'   => $latest_version,
            'url'           => 'https://github.com/' . self::GITHUB_REPO_USER . '/' . self::GITHUB_REPO_NAME,
            'package'       => $package,
            'icons'         => array(),
            'banners'       => array(),
            'banners_count' => 0,
            'requires'      => '5.8',
            'tested'        => get_bloginfo('version'),
            'requires_php'  => '7.4',
        );

        if (version_compare($this->current_version, $latest_version, '<')) {
            if (!isset($transient->response)) $transient->response = array();
            $transient->response[$this->plugin_slug] = $item;
        } else {
            // Adding to no_update ensures the "Enable auto-updates" link appears in the plugins table
            if (!isset($transient->no_update)) $transient->no_update = array();
            $transient->no_update[$this->plugin_slug] = $item;
        }

        return $transient;
    }

    /**
     * Rename GitHub zip top-level folder to omnixep-woocommerce so WordPress replaces the plugin correctly.
     */
    public function rename_github_folder($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $source;
        }

        $expected_slug = 'omnixep-woocommerce';
        $source_base = trailingslashit($remote_source);

        // Scenario 1: The correct folder is already at the root of unzipped source
        $correct_source = $source_base . $expected_slug . '/';
        if ($wp_filesystem->exists($correct_source)) {
            return $correct_source;
        }

        // Scenario 2: GitHub repo zip structure (repo-name-tag/plugin-slug/)
        // We look inside the first directory found for our plugin slug
        $dir_list = $wp_filesystem->dirlist($source_base);
        if ($dir_list) {
            foreach ($dir_list as $file) {
                if ($file['type'] === 'd') {
                    $potential = trailingslashit($source_base . $file['name']) . $expected_slug . '/';
                    if ($wp_filesystem->exists($potential)) {
                        return $potential;
                    }
                }
            }
        }

        // Fallback: If no subfolder matches, rename the first folder found to our slug (legacy behavior)
        if ($dir_list) {
            foreach ($dir_list as $file) {
                if ($file['type'] === 'd') {
                    $path = $source_base . $file['name'];
                    if ($file['name'] !== $expected_slug && $wp_filesystem->move($path, $correct_source, true)) {
                        return $correct_source;
                    }
                    break;
                }
            }
        }

        return $source;
    }

    /**
     * Force refresh of GitHub cache (e.g. from Plugins page or admin).
     */
    public static function clear_cache() {
        delete_transient(self::CACHE_TRANSIENT);
    }
}
