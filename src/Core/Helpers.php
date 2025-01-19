<?php

namespace Metafiller\Core;

class Helpers {
    /**
     * Count total published posts for a specific post type.
     *
     * @param string $post_type Post type to count.
     * @return int Total number of published posts.
     */
    public static function countPosts($post_type) {
        $count = wp_count_posts($post_type);
        return isset($count->publish) ? $count->publish : 0;
    }

    /**
     * Count posts with populated meta fields.
     *
     * @param string $post_type Post type to analyze.
     * @param string $meta_key Meta key to check.
     * @return int Number of posts with populated meta fields.
     */

    /**
     * For further developement, changing way plugin gets meta fields for more efficient and faster.
     */
    public static function countPopulatedMeta($post_type, $meta_key) {

        $query_args = [
            'post_type'      => $post_type,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- This query is intentional and executed only once during plugin initialization.
            'meta_key'       => $meta_key,
            'meta_compare'   => 'EXISTS',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];
        $posts = get_posts($query_args);
        return count($posts);
    }

    /**
     * Fetch all terms for a taxonomy (including empty terms).
     *
     * @param string $taxonomy Taxonomy to fetch terms for.
     * @return array List of terms or an empty array.
     */
    public static function fetchTerms($taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
//            error_log("Helpers: Taxonomy {$taxonomy} does not exist");
            return [];
        }

        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false, // Include empty terms
        ]);

        if (is_wp_error($terms)) {
//            error_log("Helpers: Error fetching terms for taxonomy {$taxonomy}: " . $terms->get_error_message());
            return [];
        }

        return $terms;
    }

    /**
     * Fetch taxonomy meta data for Yoast SEO.
     *
     * @param string $taxonomy Taxonomy slug.
     * @param int $term_id Term ID.
     * @return array Associative array with 'title' and 'description'.
     */
    public static function fetchYoastTaxonomyMeta($taxonomy, $term_id) {
        $meta = get_option('wpseo_taxonomy_meta', []);
        if (isset($meta[$taxonomy][$term_id])) {
            return [
                'title'       => $meta[$taxonomy][$term_id]['wpseo_title'] ?? '',
                'description' => $meta[$taxonomy][$term_id]['wpseo_desc'] ?? '',
            ];
        }

        return ['title' => '', 'description' => ''];
    }

    /**
     * Fetch taxonomy meta data for Rank Math.
     *
     * @param int $term_id Term ID.
     * @return array Associative array with 'title' and 'description'.
     */
    public static function fetchRankMathTaxonomyMeta($term_id) {
        return [
            'title'       => get_term_meta($term_id, 'rank_math_title', true),
            'description' => get_term_meta($term_id, 'rank_math_description', true),
        ];
    }

    /**
     * Fetch taxonomy meta data for AIOSEO.
     *
     * @param int $term_id Term ID.
     * @return array Associative array with 'title' and 'description'.
     */
    public static function fetchAIOSEOTaxonomyMeta($term_id) {
        return [
            'title'       => get_term_meta($term_id, '_aioseo_title', true),
            'description' => get_term_meta($term_id, '_aioseo_description', true),
        ];
    }

    /**
     * General method to fetch taxonomy meta data for a given plugin.
     *
     * @param string $plugin Plugin name (yoast, rankmath, aioseo).
     * @param string $taxonomy Taxonomy slug.
     * @param int $term_id Term ID.
     * @return array Associative array with 'title' and 'description'.
     */
    public static function getTaxonomyMeta($plugin, $taxonomy, $term_id) {
        switch ($plugin) {
            case 'yoast':
                return self::fetchYoastTaxonomyMeta($taxonomy, $term_id);

            case 'rankmath':
                return self::fetchRankMathTaxonomyMeta($term_id);

            case 'aioseo':
                return self::fetchAIOSEOTaxonomyMeta($term_id);

            default:
                return ['title' => '', 'description' => ''];
        }
    }

    /**
     * Count taxonomy terms with populated meta fields for a given plugin.
     *
     * @param string $plugin Plugin name (yoast, rankmath, aioseo).
     * @param string $taxonomy Taxonomy slug.
     * @param string $meta_key Meta key (optional, used for rankmath and aioseo).
     * @return int Count of terms with populated meta fields.
     */
    public static function countPopulatedTaxonomyMeta($plugin, $taxonomy, $meta_key = '') {
        $terms = self::fetchTerms($taxonomy);

        if ($plugin === 'yoast') {
            $meta = get_option('wpseo_taxonomy_meta', []);
            if (isset($meta[$taxonomy])) {
                return count(array_filter($meta[$taxonomy], function ($term_meta) {
                    return !empty($term_meta['wpseo_title']) || !empty($term_meta['wpseo_desc']);
                }));
            }
            return 0;
        }

        if (in_array($plugin, ['rankmath', 'aioseo']) && !empty($meta_key)) {
            return count(array_filter($terms, function ($term) use ($meta_key) {
                return get_term_meta($term->term_id, $meta_key, true);
            }));
        }

        return 0;
    }

    public static function removeYoastMetadata($type, $id, $taxonomy = '') {
//        error_log("removeYoastMetadata fired");

        if ($type === 'post') {
            // Remove Yoast metadata for posts
            delete_post_meta($id, '_yoast_wpseo_title');
            delete_post_meta($id, '_yoast_wpseo_metadesc');
        } elseif ($type === 'term' && $taxonomy) {
            // Remove specific Yoast metadata fields for taxonomy terms
            $taxonomy_meta = get_option('wpseo_taxonomy_meta', []);

//            error_log("Before removal: " . print_r($taxonomy_meta, true));

            if (isset($taxonomy_meta[$taxonomy][$id])) {
                unset($taxonomy_meta[$taxonomy][$id]['wpseo_title']);
                unset($taxonomy_meta[$taxonomy][$id]['wpseo_desc']);
                update_option('wpseo_taxonomy_meta', $taxonomy_meta);

//                error_log("After removal: " . print_r($taxonomy_meta, true));
//                error_log("Yoast metadata removed for term ID {$id} in taxonomy {$taxonomy}");
            } else {
//                error_log("No metadata found for term ID {$id} in taxonomy {$taxonomy}");
            }
        }
    }

    public static function removeYoastPostMetadata($post_id)
    {
//        error_log("removeYoastPostMetadata fired for post ID {$post_id}");

        // Remove Yoast metadata for posts
        delete_post_meta($post_id, '_yoast_wpseo_title');
        delete_post_meta($post_id, '_yoast_wpseo_metadesc');

//        error_log("Yoast metadata removed for post ID {$post_id}");
    }

    public static function removeYoastTermMetadata($term_id, $taxonomy)
    {
//        error_log("removeYoastTermMetadata fired for term ID {$term_id} in taxonomy {$taxonomy}");

        // Retrieve existing Yoast taxonomy metadata
        $taxonomy_meta = get_option('wpseo_taxonomy_meta', []);

//        error_log("Before removal: " . print_r($taxonomy_meta, true));

        if (isset($taxonomy_meta[$taxonomy][$term_id])) {
            // Remove the entire term metadata entry
            unset($taxonomy_meta[$taxonomy][$term_id]);
            update_option('wpseo_taxonomy_meta', $taxonomy_meta);

//            error_log("After removal: " . print_r($taxonomy_meta, true));
//            error_log("Yoast metadata fully removed for term ID {$term_id} in taxonomy {$taxonomy}");
        } else {
//            error_log("No metadata found for term ID {$term_id} in taxonomy {$taxonomy}");
        }
    }


    public static function updateYoastTaxonomyMeta($taxonomy, $term_id, $meta) {
//        error_log("updateYoastTaxonomyMeta fired");

        // Fetch the existing taxonomy metadata
        $taxonomy_meta = get_option('wpseo_taxonomy_meta', []);

        // Debug: Log current state of taxonomy metadata
//        error_log("Before update: " . print_r($taxonomy_meta, true));

        // Ensure taxonomy and term structures exist
        if (!isset($taxonomy_meta[$taxonomy])) {
            $taxonomy_meta[$taxonomy] = [];
        }

        if (!isset($taxonomy_meta[$taxonomy][$term_id])) {
            $taxonomy_meta[$taxonomy][$term_id] = [];
        }

        // Update the metadata, preserving existing values
        $taxonomy_meta[$taxonomy][$term_id]['wpseo_title'] = !empty($meta['title'])
            ? $meta['title']
            : ($taxonomy_meta[$taxonomy][$term_id]['wpseo_title'] ?? '');
        $taxonomy_meta[$taxonomy][$term_id]['wpseo_desc'] = !empty($meta['description'])
            ? $meta['description']
            : ($taxonomy_meta[$taxonomy][$term_id]['wpseo_desc'] ?? '');

        // Debug: Log updated taxonomy metadata
//        error_log("After update: " . print_r($taxonomy_meta, true));

        // Save the updated metadata
        update_option('wpseo_taxonomy_meta', $taxonomy_meta);

        // Clear cache to ensure changes are reflected
        wp_cache_flush();

//        error_log("Updated Yoast metadata for term ID {$term_id} in taxonomy {$taxonomy}");
    }

}
