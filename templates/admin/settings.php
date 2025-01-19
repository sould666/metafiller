<div class="wrap">
	<h2><?php esc_html_e( 'Settings', 'metafiller' ); ?></h2>
	<form method="post" action="">
		<?php wp_nonce_field( 'metafiller_settings', 'metafiller_nonce' ); ?>

		<table class="form-table">
			<!-- OpenAI API Key -->
			<tr>
				<th><label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'metafiller' ); ?></label></th>
				<td>
					<input type="text" name="openai_api_key" id="openai_api_key" class="regular-text"
							value="<?php echo esc_attr( get_option( 'metafiller_openai_api_key', '' ) ); ?>">
				</td>
			</tr>

			<!-- Agreement Checkbox -->
			<tr>
				<th><?php esc_html_e( 'Agreement', 'metafiller' ); ?></th>
				<td>
					<input type="checkbox" name="agreement" id="agreement" value="1"
						<?php checked( get_option( 'metafiller_agreement', false ), 1 ); ?>>
					<label for="agreement"><?php esc_html_e( 'I agree to send content to OpenAI for generating meta titles and descriptions. Sensitive data should not be included.', 'metafiller' ); ?></label>
				</td>
			</tr>

			<!-- Language Selection -->
			<tr>
				<th><label for="metafiller_language"><?php esc_html_e( 'Output Language', 'metafiller' ); ?></label></th>
				<td>
					<select name="metafiller_language" id="metafiller_language">
						<option value="gb" <?php selected( get_option( 'metafiller_language', 'gb' ), 'gb' ); ?>>
							<?php esc_html_e( 'English (GB)', 'metafiller' ); ?>
						</option>
						<option value="pl" <?php selected( get_option( 'metafiller_language', 'gb' ), 'pl' ); ?>>
							<?php esc_html_e( 'Polish (PL)', 'metafiller' ); ?>
						</option>
					</select>
				</td>
			</tr>

			<!-- Repopulation Options -->
			<tr>
				<th><label><?php esc_html_e( 'Repopulate Meta Fields', 'metafiller' ); ?></label></th>
				<td>
					<input type="radio" name="metafiller_repopulate" value="all"
						<?php checked( get_option( 'metafiller_repopulate', 'empty' ), 'all' ); ?>>
					<?php esc_html_e( 'Repopulate All Meta Fields', 'metafiller' ); ?><br>
					<input type="radio" name="metafiller_repopulate" value="empty"
						<?php checked( get_option( 'metafiller_repopulate', 'empty' ), 'empty' ); ?>>
					<?php esc_html_e( 'Only Populate Empty Meta Fields', 'metafiller' ); ?>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="save_settings" class="button button-primary"
					value="<?php esc_attr_e( 'Save Settings', 'metafiller' ); ?>">
		</p>
	</form>
</div>

<?php
// Validate and process the form submission
if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['save_settings'] ) ) {
	check_admin_referer( 'metafiller_settings', 'metafiller_nonce' );

	// Sanitize and save settings
	$api_key    = isset( $_POST['openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) ) : '';
	$agreement  = isset( $_POST['agreement'] ) ? 1 : 0;
	$language   = isset( $_POST['metafiller_language'] ) ? sanitize_text_field( wp_unslash( $_POST['metafiller_language'] ) ) : '';
	$repopulate = isset( $_POST['metafiller_repopulate'] ) ? sanitize_text_field( wp_unslash( $_POST['metafiller_repopulate'] ) ) : '';

	update_option( 'metafiller_openai_api_key', $api_key );
	update_option( 'metafiller_agreement', $agreement );
	update_option( 'metafiller_language', $language );
	update_option( 'metafiller_repopulate', $repopulate );

	// Display a success message
	echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'metafiller' ) . '</p></div>';

	// Redirect to reload the page
	wp_safe_redirect( admin_url( 'admin.php?page=metafiller_dashboard&tab=settings' ) );
	exit;
}
?>
