				<tr>
					<th scope="row"><?php _e('Automatic removal:', 'fg-magento-to-woocommerce'); ?></th>
					<td><input id="automatic_empty" name="automatic_empty" type="checkbox" value="1" <?php checked($data['automatic_empty'], 1); ?> /> <label for="automatic_empty" ><?php _e('Automatically remove all the WordPress content before each import', 'fg-magento-to-woocommerce'); ?></label></td>
				</tr>
				<tr>
					<th scope="row" colspan="2"><h3><?php _e('Magento web site parameters', 'fg-magento-to-woocommerce'); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="url"><?php _e('URL of the live Magento web site', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="url" name="url" type="text" size="50" value="<?php echo $data['url']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row" colspan="2"><h3><?php _e('Magento database parameters', 'fg-magento-to-woocommerce'); ?></h3></th>
				</tr>
				<tr>
					<th scope="row"><label for="hostname"><?php _e('Hostname', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="hostname" name="hostname" type="text" size="50" value="<?php echo $data['hostname']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="port"><?php _e('Port', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="port" name="port" type="text" size="50" value="<?php echo $data['port']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="database"><?php _e('Database', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="database" name="database" type="text" size="50" value="<?php echo $data['database']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="username"><?php _e('Username', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="username" name="username" type="text" size="50" value="<?php echo $data['username']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="password"><?php _e('Password', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="password" name="password" type="password" size="50" value="<?php echo $data['password']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="prefix"><?php _e('Magento table prefix', 'fg-magento-to-woocommerce'); ?></label></th>
					<td><input id="prefix" name="prefix" type="text" size="50" value="<?php echo $data['prefix']; ?>" /></td>
				</tr>
				<tr>
					<th scope="row">&nbsp;</th>
					<td><?php submit_button( __('Test the database connection', 'fg-magento-to-woocommerce'), 'secondary', 'test_database' ); ?>
					<span id="database_test_message" class="action_message"></span></td>
				</tr>
