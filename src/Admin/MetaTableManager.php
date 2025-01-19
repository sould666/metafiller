<?php
namespace Metafiller\Admin;

use Metafiller\Core\Helpers;

class MetaTableManager {
    const ITEMS_PER_PAGE = 20; // Number of items per page

    /**
     * Render the meta data table with filters, pagination, and bulk actions.
     */
    public static function renderDetailedTable() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        // Fetch filter values from the request
        $selected_plugin = $_GET['plugin'] ?? 'all';
        $selected_type = $_GET['type'] ?? 'all';
        $search_query = $_GET['s'] ?? '';

        // Get filtered data
        $filtered_data = self::getFilteredData($selected_plugin, $selected_type, $search_query);

        // Paginate the data
        $total_items = count($filtered_data);
        $data = array_slice($filtered_data, ($current_page - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);

        // Render the filter form
        self::renderFilters($selected_plugin, $selected_type, $search_query);

        // Render the table
        // Display Meta Completion Percentage
        $completion_class = '';
        $completion_percentage = self::calculateMetaCompletion($filtered_data);
        if ($completion_percentage <= 50) {
            $completion_class = 'completion-red';
        } elseif ($completion_percentage <= 75) {
            $completion_class = 'completion-orange';
        } elseif ($completion_percentage <= 99) {
            $completion_class = 'completion-yellow';
        } else {
            $completion_class = 'completion-green';
        }

        echo '<p>';
        echo '<span class="meta-label">' . esc_html(__('Meta Completion:', 'metafiller')) . '</span> ';
        echo '<span class="meta-value ' . esc_attr($completion_class) . '">' . esc_html(sprintf(__('%s%%', 'metafiller'), $completion_percentage)) . '</span>';
        echo '</p>';
        echo '<form method="post" id="meta-table-form">';
        wp_nonce_field('metafiller_bulk_action', 'metafiller_nonce');

        echo '<table class="widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th><input type="checkbox" id="select-all"></th>';
        echo '<th>' . esc_html__('ID', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('Type', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('Name', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('Yoast Title', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('Yoast Description', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('AIOSEO Title', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('AIOSEO Description', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('RankMath Title', 'metafiller') . '</th>';
        echo '<th>' . esc_html__('RankMath Description', 'metafiller') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (!empty($data)) {
            foreach ($data as $row) {
                echo '<tr>';
                echo '<td><input type="checkbox" name="selected[]" value="' . esc_attr($row['id']) . '"></td>';
                echo '<td>' . esc_html($row['id']) . '</td>';
                echo '<td>' . esc_html($row['type']) . '</td>';
                echo '<td>' . esc_html($row['name']) . '</td>';
                echo '<td class="meta-column editable" data-id="' . esc_attr($row['id']) . '" data-field="yoast_title" data-max-length="60">'
                    . esc_html($row['yoast_title']) . '</td>';
                echo '<td class="meta-column editable" data-id="' . esc_attr($row['id']) . '" data-field="yoast_desc" data-max-length="160">'
                    . esc_html($row['yoast_desc']) . '</td>';
                echo '<td class="meta-column disabled" data-id="' . esc_attr($row['id']) . '" data-field="aioseo_title" data-max-length="60">'
                    . esc_html($row['aioseo_title']) . '</td>';
                echo '<td class="meta-column disabled" data-id="' . esc_attr($row['id']) . '" data-field="aioseo_desc" data-max-length="160">'
                    . esc_html($row['aioseo_desc']) . '</td>';
                echo '<td class="meta-column editable" data-id="' . esc_attr($row['id']) . '" data-field="rankmath_title" data-max-length="60">'
                    . esc_html($row['rankmath_title']) . '</td>';
                echo '<td class="meta-column editable" data-id="' . esc_attr($row['id']) . '" data-field="rankmath_desc" data-max-length="160">'
                    . esc_html($row['rankmath_desc']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="10">' . esc_html__('No data found.', 'metafiller') . '</td></tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Pagination
        self::renderPagination($total_items, $current_page);

        // Bulk action buttons
        echo '<div class="bulk-actions">';
        echo '<select name="bulk_action">';
        echo '<option value="">' . esc_html__('Bulk Actions', 'metafiller') . '</option>';
        echo '<option value="delete">' . esc_html__('Delete', 'metafiller') . '</option>';
        echo '<option value="update">' . esc_html__('Update Meta', 'metafiller') . '</option>';
        echo '</select>';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Apply', 'metafiller') . '</button>';
        echo '</div>';

        echo '</form>';
    }

    /**
     * Render pagination controls.
     */
    private static function renderPagination($total_items, $current_page) {
        $total_pages = ceil($total_items / self::ITEMS_PER_PAGE);

        if ($total_pages <= 1) {
            return; // No pagination needed
        }

        $base_url = remove_query_arg('paged');
        echo '<div class="tablenav-pages">';
        echo paginate_links([
            'base'      => add_query_arg('paged', '%#%', $base_url),
            'format'    => '?paged=%#%',
            'current'   => $current_page,
            'total'     => $total_pages,
            'prev_text' => '« Previous',
            'next_text' => 'Next »',
        ]);
        echo '</div>';
    }

    /**
     * Calculate the percentage of filled meta titles and descriptions.
     */
    private static function calculateMetaCompletion($data) {
        $total_count = count($data);
        if ($total_count === 0) {
            return 0;
        }

        $filled_count = 0;
        foreach ($data as $row) {
            if (!empty($row['yoast_title']) || !empty($row['yoast_desc']) ||
                !empty($row['aioseo_title']) || !empty($row['aioseo_desc']) ||
                !empty($row['rankmath_title']) || !empty($row['rankmath_desc'])) {
                $filled_count++;
            }
        }

        return round(($filled_count / $total_count) * 100, 2);
    }

    /**
     * Render the filters form.
     */
    private static function renderFilters($selected_plugin, $selected_type, $search_query) {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="metafiller_dashboard">';
        echo '<input type="hidden" name="tab" value="meta-details">';

        echo '<label for="plugin">Filter by Plugin:</label>';
        echo '<select name="plugin" id="plugin">';
        echo '<option value="all"' . selected($selected_plugin, 'all', false) . '>All Plugins</option>';
        echo '<option value="yoast"' . selected($selected_plugin, 'yoast', false) . '>Yoast</option>';
        echo '<option value="aioseo"' . selected($selected_plugin, 'aioseo', false) . '>AIOSEO</option>';
        echo '<option value="rankmath"' . selected($selected_plugin, 'rankmath', false) . '>RankMath</option>';
        echo '</select>';

        echo '<label for="type">Filter by Type:</label>';
        echo '<select name="type" id="type">';
        echo '<option value="all"' . selected($selected_type, 'all', false) . '>All Types</option>';
        echo '<option value="post"' . selected($selected_type, 'post', false) . '>Post</option>';
        echo '<option value="page"' . selected($selected_type, 'page', false) . '>Page</option>';
        echo '<option value="product"' . selected($selected_type, 'product', false) . '>Product</option>';
        echo '<option value="category"' . selected($selected_type, 'category', false) . '>Category</option>';
        echo '<option value="product_category"' . selected($selected_type, 'product_category', false) . '>Product Category</option>';
        echo '</select>';

        echo '<label for="search">Search:</label>';
        echo '<input type="text" name="s" id="search" value="' . esc_attr($search_query) . '">';

        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';
    }

    /**
     * Get filtered data based on plugin, type, and search query.
     */
    private static function getFilteredData($plugin, $type, $query) {
        $all_data = get_option('metafiller_meta_data', ['detailed_data' => []])['detailed_data'];

        return array_values(array_filter($all_data, function ($row) use ($plugin, $type, $query) {
            $plugin_match = $plugin === 'all' || !empty($row[$plugin . '_title']);

            // Adjust the type match to support specific post types
            $type_match = $type === 'all'
                || ($type === 'post' && isset($row['type']) && $row['type'] === 'post')
                || ($type === 'page' && isset($row['type']) && $row['type'] === 'page')
                || ($type === 'product' && isset($row['type']) && $row['type'] === 'product')
                || ($type === 'category' && isset($row['type']) && $row['type'] === 'category')
                || ($type === 'product_category' && isset($row['type']) && $row['type'] === 'product_category');

            $query_match = empty($query) || stripos($row['name'] . ' ' . implode(' ', array_values($row)), $query) !== false;

            return $plugin_match && $type_match && $query_match;
        }));
    }

    /**
     * Process bulk actions: delete or update metadata. Relevant for now
     */
    public static function processBulkActions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('metafiller_bulk_action', 'metafiller_nonce')) {
            $action = $_POST['bulk_action'] ?? '';
            $selected_ids = $_POST['selected'] ?? [];

            if (empty($action) || empty($selected_ids)) {
                return;
            }

            foreach ($selected_ids as $id) {
                if ($action === 'delete') {
                    Helpers::removeYoastMetadata('post', $id);
                    // Extend for terms and other plugins
                } elseif ($action === 'update') {
                    update_post_meta($id, '_custom_meta', 'Updated Value');
                }
            }
        }
    }
}
