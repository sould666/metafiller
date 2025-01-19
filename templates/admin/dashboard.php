<div class="wrap">
    <h2><?php esc_html_e('Metafiller Dashboard', 'metafiller'); ?></h2>
    <p><?php esc_html_e('The Metafiller plugin helps you manage and optimize meta data for your website.', 'metafiller'); ?></p>

    <!-- SEO Plugins Status -->
    <h3><?php esc_html_e('SEO Plugins Status', 'metafiller'); ?></h3>
    <table class="widefat striped">
        <thead>
        <tr>
            <th><?php esc_html_e('Plugin Name', 'metafiller'); ?></th>
            <th><?php esc_html_e('Status', 'metafiller'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($seo_plugins)) : ?>
            <?php foreach ($seo_plugins as $plugin) : ?>
                <tr>
                    <td><?php echo esc_html($plugin['name']); ?></td>
                    <td>
                        <?php if ($plugin['is_installed']) : ?>
                            <?php if ($plugin['is_active']) : ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span>
                                <strong><?php esc_html_e('Active', 'metafiller'); ?></strong>
                            <?php else : ?>
                                <span class="dashicons dashicons-warning" style="color: orange;"></span>
                                <em><?php esc_html_e('Installed but Inactive', 'metafiller'); ?></em>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                            <span style="color: red;"><?php esc_html_e('Removed or Not Installed', 'metafiller'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="2"><?php esc_html_e('No SEO plugins detected.', 'metafiller'); ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Run SEO Check -->
    <h3><?php esc_html_e('Run SEO Check', 'metafiller'); ?></h3>
    <form method="post" action="">
        <?php wp_nonce_field('metafiller_seo_check', 'metafiller_nonce'); ?>
        <input type="submit" name="metafiller_run_check" value="<?php esc_attr_e('Run SEO Check', 'metafiller'); ?>" class="button button-primary">
    </form>

    <!-- Active Plugins Notice -->
    <?php if (!empty($active_plugins) && count($active_plugins) > 1) : ?>
        <div class="notice notice-warning">
            <p>
                <?php
                $active_plugin_names = array_map(function ($plugin) {
                    return $plugin['name'];
                }, array_filter($active_plugins, function ($plugin) {
                    return $plugin['is_active'];
                }));
                ?>
                <?php echo esc_html__('Multiple SEO plugins are active: ', 'metafiller') . esc_html(implode(', ', $active_plugin_names)); ?>.
                <?php esc_html_e('This may cause conflicts.', 'metafiller'); ?>
            </p>
        </div>
    <?php elseif (!empty($active_plugins)) : ?>
        <div class="notice notice-success">
            <p><?php esc_html_e('No conflicts detected between active SEO plugins.', 'metafiller'); ?></p>
        </div>
    <?php endif; ?>

</div>
