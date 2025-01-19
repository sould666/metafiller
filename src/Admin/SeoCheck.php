<?php

namespace Metafiller\Admin;

use Metafiller\Core\Helpers;

class SeoCheck {
    /**
     * Run on plugin activation: detect plugins, analyze meta data, and update options.
     */
    public static function onActivation() {
//        error_log('Metafiller Activation: Starting SEO Check');

        // Detect installed SEO plugins
        $seo_plugins = self::detectSEOPlugins();

        update_option('metafiller_seo_plugins', $seo_plugins);

        // Analyze post types and meta data
        $meta_data = self::analyzePostTypes();
        update_option('metafiller_meta_data', $meta_data);

//        error_log('Metafiller Activation: SEO Check Complete');
    }

    /**
     * Detect active SEO plugins.
     *
     * @return array List of detected SEO plugins
     */
    public static function detectSEOPlugins() {
        $seo_plugins = [];
        $active_plugins = get_option('active_plugins', []); // Get currently active plugins
        $installed_plugins = get_plugins(); // Get all installed plugins

        // Plugin paths relative to the plugins directory
        $plugin_paths = [
            'yoast' => 'wordpress-seo/wp-seo.php',
            'aioseo' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'rankmath' => 'seo-by-rank-math/rank-math.php',
        ];

        // Check for Yoast SEO
        $seo_plugins[] = [
            'name' => 'Yoast SEO',
            'is_active' => in_array($plugin_paths['yoast'], $active_plugins, true),
            'is_installed' => array_key_exists($plugin_paths['yoast'], $installed_plugins),
        ];

        // Check for All in One SEO
        $seo_plugins[] = [
            'name' => 'All in One SEO',
            'is_active' => in_array($plugin_paths['aioseo'], $active_plugins, true),
            'is_installed' => array_key_exists($plugin_paths['aioseo'], $installed_plugins),
        ];

        // Check for Rank Math
        $seo_plugins[] = [
            'name' => 'Rank Math',
            'is_active' => in_array($plugin_paths['rankmath'], $active_plugins, true),
            'is_installed' => array_key_exists($plugin_paths['rankmath'], $installed_plugins),
        ];

//        error_log('Metafiller: Detected plugins: ' . print_r($seo_plugins, true));

        return $seo_plugins;
    }







    /**
     * Analyze post types and gather meta field data.
     *
     * @return array Meta data including summary and detailed information
     */
    public static function analyzePostTypes() {
//        error_log('Metafiller: Starting analyzePostTypes method');

        $summary_data = [];
        $detailed_data = [];

        // Analyze post types
        $post_types = get_post_types(['public' => true], 'objects');
//        error_log('Metafiller: Fetched post types: ' . implode(', ', array_keys($post_types)));

        foreach ($post_types as $post_type => $post_type_obj) {
//            error_log("Metafiller: Analyzing post type: {$post_type}");

            $total_posts = Helpers::countPosts($post_type);
            $summary_data[] = [
                'type' => 'Post Type',
                'name' => $post_type,
                'total' => $total_posts,
                'yoast_title' => Helpers::countPopulatedMeta($post_type, '_yoast_wpseo_title'),
                'yoast_desc' => Helpers::countPopulatedMeta($post_type, '_yoast_wpseo_metadesc'),
                'aioseo_title' => Helpers::countPopulatedMeta($post_type, '_aioseo_title'),
                'aioseo_desc' => Helpers::countPopulatedMeta($post_type, '_aioseo_description'),
                'rankmath_title' => Helpers::countPopulatedMeta($post_type, 'rank_math_title'),
                'rankmath_desc' => Helpers::countPopulatedMeta($post_type, 'rank_math_description'),
            ];

            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);

            foreach ($posts as $post_id) {
                $detailed_data[] = [
                    'id' => $post_id,
                    'type' => 'Post',
                    'name' => get_the_title($post_id),
                    'yoast_title' => get_post_meta($post_id, '_yoast_wpseo_title', true),
                    'yoast_desc' => get_post_meta($post_id, '_yoast_wpseo_metadesc', true),
                    'aioseo_title' => get_post_meta($post_id, '_aioseo_title', true),
                    'aioseo_desc' => get_post_meta($post_id, '_aioseo_description', true),
                    'rankmath_title' => get_post_meta($post_id, 'rank_math_title', true),
                    'rankmath_desc' => get_post_meta($post_id, 'rank_math_description', true),
                ];
            }
        }

        // Analyze taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');
//        error_log('Metafiller: Fetched taxonomies: ' . implode(', ', array_keys($taxonomies)));

        foreach ($taxonomies as $taxonomy => $taxonomy_obj) {
//            error_log("Metafiller: Analyzing taxonomy: {$taxonomy}");

            $terms = Helpers::fetchTerms($taxonomy);
            if (empty($terms)) {
//                error_log("Metafiller: No terms found for taxonomy {$taxonomy}");
                continue;
            }

            $summary_data[] = [
                'type' => 'Taxonomy',
                'name' => $taxonomy_obj->labels->name,
                'total' => count($terms),
                'yoast_title' => Helpers::countPopulatedTaxonomyMeta('yoast', $taxonomy),
                'yoast_desc' => Helpers::countPopulatedTaxonomyMeta('yoast', $taxonomy),
                'aioseo_title' => Helpers::countPopulatedTaxonomyMeta('aioseo', $taxonomy, '_aioseo_title'),
                'aioseo_desc' => Helpers::countPopulatedTaxonomyMeta('aioseo', $taxonomy, '_aioseo_description'),
                'rankmath_title' => Helpers::countPopulatedTaxonomyMeta('rankmath', $taxonomy, 'rank_math_title'),
                'rankmath_desc' => Helpers::countPopulatedTaxonomyMeta('rankmath', $taxonomy, 'rank_math_description'),
            ];

            foreach ($terms as $term) {
                $term_meta = [
                    'yoast' => Helpers::getTaxonomyMeta('yoast', $taxonomy, $term->term_id),
                    'aioseo' => Helpers::getTaxonomyMeta('aioseo', $taxonomy, $term->term_id),
                    'rankmath' => Helpers::getTaxonomyMeta('rankmath', $taxonomy, $term->term_id),
                ];

                $detailed_data[] = [
                    'id' => $term->term_id,
                    'type' => 'Term',
                    'name' => $term->name,
                    'yoast_title' => $term_meta['yoast']['title'],
                    'yoast_desc' => $term_meta['yoast']['description'],
                    'aioseo_title' => $term_meta['aioseo']['title'],
                    'aioseo_desc' => $term_meta['aioseo']['description'],
                    'rankmath_title' => $term_meta['rankmath']['title'],
                    'rankmath_desc' => $term_meta['rankmath']['description'],
                ];
            }
        }

//        error_log('Metafiller: analyzePostTypes method completed');

        return [
            'summary_data' => $summary_data,
            'detailed_data' => $detailed_data,
        ];
    }

    /**
     * Detect conflicts between SEO plugins or meta data.
     *
     * @return array Active plugins and conflict status
     */
    public static function detectConflicts() {
        $active_plugins = self::detectSEOPlugins();
        $meta_conflicts = false;

        // Check for conflicts in posts
        $post_types = get_post_types(['public' => true]);
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);

            foreach ($posts as $post_id) {
                $yoast_meta = get_post_meta($post_id, '_yoast_wpseo_title', true);
                $aioseo_meta = get_post_meta($post_id, '_aioseo_title', true);
                $rankmath_meta = get_post_meta($post_id, 'rank_math_title', true);

                if (($yoast_meta && $aioseo_meta) || ($yoast_meta && $rankmath_meta) || ($aioseo_meta && $rankmath_meta)) {
                    $meta_conflicts = true;
//                    error_log("Conflict detected in post ID {$post_id}");
                    break 2;
                }
            }
        }

        // Check for conflicts in taxonomy terms
        $taxonomies = get_taxonomies(['public' => true]);
        foreach ($taxonomies as $taxonomy) {
            $terms = Helpers::fetchTerms($taxonomy);
            foreach ($terms as $term) {
                $yoast_meta = Helpers::getTaxonomyMeta('yoast', $taxonomy, $term->term_id)['title'];
                $aioseo_meta = Helpers::getTaxonomyMeta('aioseo', $taxonomy, $term->term_id)['title'];
                $rankmath_meta = Helpers::getTaxonomyMeta('rankmath', $taxonomy, $term->term_id)['title'];

                if (($yoast_meta && $aioseo_meta) || ($yoast_meta && $rankmath_meta) || ($aioseo_meta && $rankmath_meta)) {
                    $meta_conflicts = true;
//                    error_log("Conflict detected in term ID {$term->term_id} (taxonomy: {$taxonomy})");
                    break 2;
                }
            }
        }

//        error_log('Metafiller: Conflict detection completed. Conflicts found: ' . ($meta_conflicts ? 'Yes' : 'No'));

        return [
            'active_plugins' => $active_plugins,
            'meta_conflicts' => $meta_conflicts,
        ];
    }
}
