<?php

namespace Metafiller\Admin;

use AIOSEO\Plugin\Common\Models\PostMeta;
use Metafiller\Core\Helpers;
use Metafiller\Admin\SeoCheck;
use Metafiller\Admin\OpenAiHandler;

class AjaxHandler {
    public function __construct() {
        add_action('wp_ajax_metafiller_save_meta_field', [$this, 'saveMetaField']);
        add_action('wp_ajax_metafiller_generate_metas', [$this, 'generateMetas']);

    }

    public function saveMetaField() {
        check_ajax_referer('metafiller_save_meta_field', 'nonce');

        $post_id = intval($_POST['post_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_textarea_field($_POST['value']); // Using textarea sanitization for long strings

        $success = false;

        // Determine plugin and update the corresponding field
        switch ($field) {
            case 'rankmath_title':
            case 'rankmath_desc':
                $meta_key = $field === 'rankmath_title' ? 'rank_math_title' : 'rank_math_description';
                $success = $this->updateMeta($post_id, $meta_key, $value);
                break;

            case 'yoast_title':
            case 'yoast_desc':
                $meta_key = $field === 'yoast_title' ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc';
                $success = $this->updateMeta($post_id, $meta_key, $value);
                break;

            case 'aioseo_title':
            case 'aioseo_desc':
                $meta_key = $field === 'aioseo_title' ? 'title' : 'description';
                $success = $this->updateAIOSEOMeta($post_id, $meta_key, $value);
                break;

            default:
                wp_send_json_error(['message' => 'Invalid field.']);
                break;
        }

        if ($success) {
            $this->updateMetafillerMetaData();

            wp_send_json_success(['message' => 'Meta updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update meta field.']);
        }
    }

    private function updateMeta($post_id, $meta_key, $value) {
        $updated = update_post_meta($post_id, $meta_key, $value);
        if ($updated && strpos($meta_key, 'rank_math_') === 0) {
            do_action('rank_math/metadata/clear', $post_id);
        }
        return $updated;
    }

    private function updateAIOSEOMeta($post_id, $meta_key, $value) {
        // Check if the AIOSEO plugin is active
        if (!is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
//            error_log("AIOSEO plugin is not active. Cannot update meta for post ID {$post_id}");
            return false;
        }

        // Include the main plugin file if necessary
        if (!class_exists('\AIOSEO\Plugin\Common\Models\PostMeta')) {
//            error_log("AIOSEO PostMeta class not loaded. Attempting to locate and include it...");

            // Attempt to manually include the file where PostMeta is defined
            $aioseo_path = WP_PLUGIN_DIR . '/all-in-one-seo-pack/';
            $post_meta_file = $aioseo_path . 'src/Common/Models/PostMeta.php';

            if (file_exists($post_meta_file)) {
                require_once $post_meta_file;
            } else {
//                error_log("AIOSEO PostMeta file not found at expected path: {$post_meta_file}");
                return false;
            }
        }

        // Check again if the PostMeta class is available
        if (!class_exists('\AIOSEO\Plugin\Common\Models\PostMeta')) {
//            error_log("Failed to load AIOSEO PostMeta class after attempting to include it.");
            return false;
        }

        // Fetch the AIOSEO meta object for the post
        $post_meta = \AIOSEO\Plugin\Common\Models\PostMeta::getMeta($post_id);
        if (!$post_meta) {
//            error_log("Failed to retrieve AIOSEO meta for post ID {$post_id}");
            return false;
        }

        // Update the specific field
        try {
            $post_meta->{$meta_key} = $value;
            $post_meta->save(); // Save changes to the database
//            error_log("Successfully updated AIOSEO meta '{$meta_key}' for post ID {$post_id} with value: {$value}");
            return true;
        } catch (\Exception $e) {
//            error_log("Error updating AIOSEO meta for post ID {$post_id}: " . $e->getMessage());
            return false;
        }
    }



    private function updateMetafillerMetaData() {
        if (method_exists('\Metafiller\Admin\SeoCheck', 'analyzePostTypes')) {
            $meta_data = \Metafiller\Admin\SeoCheck::analyzePostTypes();
            update_option('metafiller_meta_data', $meta_data);
        } else {
//            error_log('Metafiller: analyzePostTypes method not found. Cannot update metafiller_meta_data option.');
        }
    }

    /**
     * Handle AJAX request to generate metas for posts and terms.
     */
    /**
     * Handle AJAX request to generate meta titles and descriptions.
     */
    public function generateMetas() {
        check_ajax_referer('metafiller_generate_metas', 'nonce');

        // Check agreement option
        if (!get_option('metafiller_agreement', false)) {
            wp_send_json_error([
                'message' => __('You must agree to scan posts and terms for AI-generated metas.', 'metafiller'),
            ]);
        }

        // Detect active SEO plugins
        $seo_plugins = SeoCheck::detectSEOPlugins();
        $active_plugins = array_filter($seo_plugins, fn($plugin) => $plugin['is_active']);

        if (count($active_plugins) === 0) {
            wp_send_json_error([
                'message' => __('No active SEO plugin detected. Please activate one before proceeding.', 'metafiller'),
            ]);
        }

        if (count($active_plugins) > 1) {
            wp_send_json_error([
                'message' => __('Please keep only one SEO plugin active at a time and ensure you have merged data from other plugins before proceeding.', 'metafiller'),
            ]);
        }

        $active_plugin = reset($active_plugins); // Get the single active plugin
        $plugin_name = $active_plugin['name'];
        $supported_plugins = ['Yoast SEO', 'Rank Math'];

        if (!in_array($plugin_name, $supported_plugins, true)) {
            wp_send_json_error([
                'message' => __('This SEO plugin is not supported yet. Stay tuned for future updates.', 'metafiller'),
            ]);
        }

        // Initialize OpenAI handler
        $openAiHandler = new OpenAiHandler();
        $processed_count = 0;
        $repopulate_mode = get_option('metafiller_repopulate', 'empty'); // Repopulate mode: 'all' or 'empty'

        try {
            // Process posts and pages - add additional custom type if necessary
            foreach (['post', 'page', 'product'] as $post_type) {
                $posts = get_posts([
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ]);

                foreach ($posts as $post_id) {
                    $content = get_post_field('post_content', $post_id);
                    $title = get_post_field('post_title', $post_id);

                    // Determine current meta fields based on the plugin
                    $meta_title_key = $plugin_name === 'Yoast SEO' ? '_yoast_wpseo_title' : 'rank_math_title';
                    $meta_description_key = $plugin_name === 'Yoast SEO' ? '_yoast_wpseo_metadesc' : 'rank_math_description';

                    $current_meta_title = get_post_meta($post_id, $meta_title_key, true);
                    $current_meta_description = get_post_meta($post_id, $meta_description_key, true);

                    // Skip processing if repopulation mode is 'empty' and metas are already populated
                    if ($repopulate_mode === 'empty' && !empty($current_meta_title) && !empty($current_meta_description)) {
                        continue;
                    }

                    // Generate meta fields
                    $meta_title = $openAiHandler->generateMetaTitle($title, $content);
                    $meta_description = $openAiHandler->generateMetaDescription($title, $content);

                    // Update meta fields
                    update_post_meta($post_id, $meta_title_key, $meta_title);
                    update_post_meta($post_id, $meta_description_key, $meta_description);

                    $processed_count++;
                }
            }

            // Process taxonomies
            $taxonomies = get_taxonomies(['public' => true], 'objects');
            foreach ($taxonomies as $taxonomy => $taxonomy_obj) {
                $terms = Helpers::fetchTerms($taxonomy);

                foreach ($terms as $term) {
                    $term_meta = [
                        'title' => $openAiHandler->generateMetaTitle($term->name, $term->description),
                        'description' => $openAiHandler->generateMetaDescription($term->name, $term->description),
                    ];

                    if ($plugin_name === 'Yoast SEO') {
                        Helpers::updateYoastTaxonomyMeta($taxonomy, $term->term_id, $term_meta);
                    } elseif ($plugin_name === 'Rank Math') {
                        update_term_meta($term->term_id, 'rank_math_title', $term_meta['title']);
                        update_term_meta($term->term_id, 'rank_math_description', $term_meta['description']);
                    }

                    $processed_count++;
                }
            }

            wp_send_json_success([
                'message' => __("Metadata generation complete. Processed $processed_count items.", 'metafiller'),
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error generating metadata: ', 'metafiller') . $e->getMessage(),
            ]);
        }
    }
}
