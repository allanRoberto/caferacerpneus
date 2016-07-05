<?php

/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wordpress.org/plugins/fg-magento-to-woocommerce/
 * @since      1.0.0
 *pr
 * @package    FG_Magento_to_WooCommerce
 * @subpackage FG_Magento_to_WooCommerce/admin/partials
 */
?>
<div id="fgm2wc_admin_page" class="wrap">
	<?php screen_icon(); ?>
	<h2><?php print $data['title'] ?></h2>
	
	<p><?php print $data['description'] ?></p>
	
	<div id="fgm2wc_settings">
		<?php require('database-info.php'); ?>
		<?php require('empty-content.php'); ?>
		
		
		<form id="form_import" method="post">

			<?php wp_nonce_field( 'parameters_form', 'fgm2wc_nonce' ); ?>

			<table class="form-table">
				<?php require('settings.php'); ?>
				<?php do_action('fgm2wc_post_display_settings_options'); ?>
				<?php require('behavior.php'); ?>
				<?php require('actions.php'); ?>
				<?php require('progress-bar.php'); ?>
				<?php require('logger.php'); ?>
			</table>
		</form>
		
	</div>
	
	<?php require('extra-features.php'); ?>
</div>
