<?php if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
<!-- Merge Meta Data Section -->
	<h3><?php esc_html_e( 'Merge Meta Data', 'metafiller' ); ?></h3>
	<p><?php esc_html_e( 'Be aware that AIOSEO in basic version, does not include meta title and description for terms and categories, threfore you can move it from another plugin here.', 'metafiller' ); ?></p>
	<form method="post" id="metafiller-merge-form">
		<?php wp_nonce_field( 'metafiller_meta_actions', 'metafiller_nonce' ); ?>
		<label for="metafiller_target_plugin"><?php esc_html_e( 'Select Target Plugin:', 'metafiller' ); ?></label>
		<select name="metafiller_target_plugin" id="metafiller_target_plugin">
			<option value="yoast"><?php esc_html_e( 'Yoast SEO', 'metafiller' ); ?></option>
			<option value="aioseo"><?php esc_html_e( 'All in One SEO', 'metafiller' ); ?></option>
			<option value="rankmath"><?php esc_html_e( 'Rank Math', 'metafiller' ); ?></option>
		</select>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Merge Meta Data', 'metafiller' ); ?></button>
	</form>

	<!-- Modal Confirmation -->
	<div id="metafiller-merge-modal" class="modal-content" style="display: none;">
		<h2><?php esc_html_e( 'Are you sure?', 'metafiller' ); ?></h2>
		<p><?php esc_html_e( 'Meta data for the selected plugin will be kept, and all others will be discarded. This action cannot be undone.', 'metafiller' ); ?></p>
		<div class="modal-actions">
			<button id="metafiller-proceed" class="button button-primary"><?php esc_html_e( 'Proceed', 'metafiller' ); ?></button>
			<button id="metafiller-cancel" class="button button-secondary"><?php esc_html_e( 'Cancel', 'metafiller' ); ?></button>
		</div>
	</div>
	<?php \Metafiller\Admin\SeoCheck::detectConflicts(); ?>
	<?php
	$conflict_detection = \Metafiller\Admin\SeoCheck::detectConflicts();
	$meta_conflicts     = $conflict_detection['meta_conflicts'];
	?>
	<?php if ( $meta_conflicts ) : ?>
		<div class="notice notice-error">
			<p>Meta data conflicts detected between active plugins. Consider merging meta data to avoid issues.</p>
		</div>
	<?php endif; ?>

	<!-- Summary Table -->
	<h3><?php esc_html_e( 'Meta Data Summary', 'metafiller' ); ?></h3>
	<table class="widefat striped">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Type', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'Name', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'Total', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'Yoast Titles', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'Yoast Descriptions', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'AIOSEO Titles', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'AIOSEO Descriptions', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'RankMath Titles', 'metafiller' ); ?></th>
			<th><?php esc_html_e( 'RankMath Descriptions', 'metafiller' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php if ( ! empty( $summary_data ) ) : ?>
			<?php foreach ( $summary_data as $item ) : ?>
				<tr>
					<td><?php echo esc_html( $item['type'] ); ?></td>
					<td><?php echo esc_html( $item['name'] ); ?></td>
					<td><?php echo esc_html( $item['total'] ); ?></td>
					<td><?php echo esc_html( $item['yoast_title'] ); ?></td>
					<td><?php echo esc_html( $item['yoast_desc'] ); ?></td>
					<td><?php echo esc_html( $item['aioseo_title'] ); ?></td>
					<td><?php echo esc_html( $item['aioseo_desc'] ); ?></td>
					<td><?php echo esc_html( $item['rankmath_title'] ); ?></td>
					<td><?php echo esc_html( $item['rankmath_desc'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="9"><?php esc_html_e( 'No summary data available.', 'metafiller' ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

</div>
