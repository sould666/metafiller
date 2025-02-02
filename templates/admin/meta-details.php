<?php if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h2><?php esc_html_e( 'Meta Details', 'metafiller' ); ?></h2>
	<button id="generate-metas-btn" class="button button-primary">
		<?php esc_html_e( 'Generate Metas Regarding Content', 'metafiller' ); ?>
	</button>

	<?php
	// Use MetaTableManager to render the detailed table
	\Metafiller\Admin\MetaTableManager::renderDetailedTable();
	?>
</div>
