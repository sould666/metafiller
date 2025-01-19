<?php

namespace Metafiller\Admin;

use Metafiller\Core\Helpers;

class Dashboard
{
    public static function renderDashboard()
    {
        // Handle form submissions
        if (isset($_POST['metafiller_run_check'])) {
            self::performCheck();
        }

        self::handleActions();

        // Detect the current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Render navigation tabs
        self::renderTabs($current_tab);

        // Route to the correct template based on the current tab
        switch ($current_tab) {
            case 'meta-details':
                self::loadTemplate('admin/meta-details', [
                    'detailed_data' => get_option('metafiller_meta_data', [])['detailed_data'] ?? [],
                ]);
                break;

            case 'meta-merge':
                self::loadTemplate('admin/meta-merge', [
                    'summary_data' => get_option('metafiller_meta_data', [])['summary_data'] ?? [],
                    'meta_conflicts' => SeoCheck::detectConflicts()['meta_conflicts'] ?? false,
                ]);
                break;

            case 'settings':
                self::loadTemplate('admin/settings', []);
                break;

            case 'dashboard':
            default:
                $seo_plugins = SeoCheck::detectSEOPlugins();
                $conflicts = SeoCheck::detectConflicts();

                self::loadTemplate('admin/dashboard', [
                    'seo_plugins' => $seo_plugins,
                    'active_plugins' => $conflicts['active_plugins'] ?? [],
                ]);
                break;
        }
    }

    public static function performCheck()
    {
        if (!isset($_POST['metafiller_nonce']) || !wp_verify_nonce($_POST['metafiller_nonce'], 'metafiller_seo_check')) {
            wp_die('Security check failed.');
        }

        SeoCheck::onActivation();
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>SEO check completed successfully.</p></div>';
        });
    }

    private static function loadTemplate($template, $data = [])
    {
        $file = METAFILLER_PLUGIN_DIR . 'templates/' . $template . '.php';

        if (file_exists($file)) {
            extract($data);
            include $file;
        } else {
            Helpers::log('Template not found: ' . $template);
        }
    }

    private static function renderTabs($current_tab)
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Metafiller Dashboard', 'metafiller'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=metafiller_dashboard&tab=dashboard" class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Dashboard', 'metafiller'); ?>
                </a>
                <a href="?page=metafiller_dashboard&tab=meta-merge" class="nav-tab <?php echo $current_tab === 'meta-merge' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Merge Metadata', 'metafiller'); ?>
                </a>
                <a href="?page=metafiller_dashboard&tab=meta-details" class="nav-tab <?php echo $current_tab === 'meta-details' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Meta Details', 'metafiller'); ?>
                </a>
                <a href="?page=metafiller_dashboard&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'metafiller'); ?>
                </a>
            </h2>
        </div>
        <?php
    }

    private static function handleActions()
    {
        if (isset($_POST['metafiller_nonce']) && wp_verify_nonce($_POST['metafiller_nonce'], 'metafiller_meta_actions')) {
            if (isset($_POST['metafiller_merge_meta'])) {
                $target_plugin = sanitize_text_field($_POST['metafiller_target_plugin']);
//                error_log('Selected target plugin: ' . $target_plugin);

                if (empty($target_plugin)) {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-error is-dismissible"><p>No target plugin selected. Please try again.</p></div>';
                    });
                    return;
                }

                self::mergeMetaData($target_plugin);
                SeoCheck::onActivation();

                add_action('admin_notices', function () use ($target_plugin) {
                    echo '<div class="notice notice-success is-dismissible"><p>Meta data successfully merged and refreshed for ' . esc_html($target_plugin) . '.</p></div>';
                });
            }
        }
    }

    /**
     * Merge Metadata for 3 plugins
     * @param $target_plugin
     * @return void
     */
    private static function mergeMetaData($target_plugin)
    {
//        error_log("merge metadata fired for target plugin: {$target_plugin}");

        $post_types = get_post_types(['public' => true]);
        $taxonomies = get_taxonomies(['public' => true]);
        $meta_keys = [
            'yoast' => ['title' => '_yoast_wpseo_title', 'desc' => '_yoast_wpseo_metadesc'],
            'aioseo' => ['title' => '_aioseo_title', 'desc' => '_aioseo_description'],
            'rankmath' => ['title' => 'rank_math_title', 'desc' => 'rank_math_description'],
        ];

        if (!isset($meta_keys[$target_plugin])) {
//            error_log("Invalid target plugin: {$target_plugin}");
            return;
        }

        $target_meta = $meta_keys[$target_plugin];

        /**
         * Merge metadata for posts and pages
         */
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);

            foreach ($posts as $post_id) {
                // Fetch metadata for all plugins
                $yoast_meta = [
                    'title' => get_post_meta($post_id, '_yoast_wpseo_title', true),
                    'desc' => get_post_meta($post_id, '_yoast_wpseo_metadesc', true),
                ];
                $aioseo_meta = [
                    'title' => get_post_meta($post_id, '_aioseo_title', true),
                    'desc' => get_post_meta($post_id, '_aioseo_description', true),
                ];
                $rankmath_meta = [
                    'title' => get_post_meta($post_id, 'rank_math_title', true),
                    'desc' => get_post_meta($post_id, 'rank_math_description', true),
                ];

                // Determine the source metadata (keep existing target_plugin metadata if none is available)
                $source_meta = [
                    'title' => $yoast_meta['title'] ?: $aioseo_meta['title'] ?: $rankmath_meta['title'] ?: '',
                    'description' => $yoast_meta['desc'] ?: $aioseo_meta['desc'] ?: $rankmath_meta['desc'] ?: '',
                ];

                // Skip if source metadata is empty (nothing to merge)
                if (empty($source_meta['title']) && empty($source_meta['description'])) {
//                    error_log("No metadata to merge for post ID {$post_id}");
                    continue;
                }

                // Update metadata for the target plugin
                update_post_meta($post_id, $target_meta['title'], $source_meta['title']);
                update_post_meta($post_id, $target_meta['desc'], $source_meta['description']);
//                error_log("Updated metadata for post ID {$post_id}: " . print_r($source_meta, true));

                // Remove metadata from other plugins
                foreach ($meta_keys as $plugin => $keys) {
                    if ($plugin === 'yoast' && $plugin !== $target_plugin) {
                        Helpers::removeYoastPostMetadata($post_id); // Handle post metadata
                    } elseif ($plugin !== $target_plugin) {
                        delete_post_meta($post_id, $keys['title']);
                        delete_post_meta($post_id, $keys['desc']);
                    }
                }
            }
        }

        /**
         * Merge metadata for taxonomy terms
         */
        foreach ($taxonomies as $taxonomy) {
            $terms = Helpers::fetchTerms($taxonomy);

            foreach ($terms as $term) {
                // Fetch metadata for all plugins
                $yoast_meta = Helpers::getTaxonomyMeta('yoast', $taxonomy, $term->term_id);
                $aioseo_meta = Helpers::getTaxonomyMeta('aioseo', $taxonomy, $term->term_id);
                $rankmath_meta = Helpers::getTaxonomyMeta('rankmath', $taxonomy, $term->term_id);

                // Determine the source metadata
                $source_meta = [
                    'title' => $yoast_meta['title'] ?: $aioseo_meta['title'] ?: $rankmath_meta['title'] ?: '',
                    'description' => $yoast_meta['description'] ?: $aioseo_meta['description'] ?: $rankmath_meta['description'] ?: '',
                ];

                // Skip if source metadata is empty
                if (empty($source_meta['title']) && empty($source_meta['description'])) {
//                    error_log("No metadata to merge for term ID {$term->term_id} in taxonomy {$taxonomy}");
                    continue;
                }

                // Update metadata for the target plugin
                if ($target_plugin === 'yoast') {
                    Helpers::updateYoastTaxonomyMeta($taxonomy, $term->term_id, $source_meta);
                } else {
                    update_term_meta($term->term_id, $target_meta['title'], $source_meta['title']);
                    update_term_meta($term->term_id, $target_meta['desc'], $source_meta['description']);
                }
//                error_log("Updated metadata for term ID {$term->term_id} in taxonomy {$taxonomy}: " . print_r($source_meta, true));

                // Remove metadata from other plugins
                foreach ($meta_keys as $plugin => $keys) {
                    if ($plugin === 'yoast' && $plugin !== $target_plugin) {
                        Helpers::removeYoastTermMetadata($term->term_id, $taxonomy); // Handle term metadata
                    } elseif ($plugin !== $target_plugin) {
                        delete_term_meta($term->term_id, $keys['title']);
                        delete_term_meta($term->term_id, $keys['desc']);
                    }
                }
            }
        }

//        error_log('Metadata merged successfully for target plugin: ' . $target_plugin);
    }
}
