=== FG Magento to WooCommerce ===
Contributors: Frédéric GILLES
Plugin Uri: https://wordpress.org/plugins/fg-magento-to-woocommerce/
Tags: magento, woocommerce, importer, convert magento to wordpress, migrate magento to wordpress, magento to wordpress migration, migrator, converter, import
Requires at least: 4.4
Tested up to: 4.5.3
Stable tag: 2.4.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=fred%2egilles%40free%2efr&lc=FR&item_name=fg-magento-to-woocommerce&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted

A plugin to migrate product categories, products, images and CMS from Magento to WooCommerce

== Description ==

This plugin migrates product categories, products, images and CMS from Magento to WooCommerce.

It has been tested with **Magento versions 1.3 to 1.9** and **Wordpress 4.5**. It is compatible with multisite installations.

Major features include:

* migrates the product categories
* migrates the product categories images
* migrates the products
* migrates the product thumbnails
* migrates the product images galleries
* migrates the product stocks
* migrates the CMS

No need to subscribe to an external web site.

= Premium version =

The **Premium version** includes these extra features:

* migrates the product attributes
* migrates the product variations
* migrates the grouped products
* migrates the users
* migrates the customers
* authenticate the users and the customers in WordPress with their Magento passwords
* migrates the orders
* migrates the SEO meta data
* multistores: Option to choose which store to import
* update the already imported products stocks and orders

The Premium version can be purchased on: http://www.fredericgilles.net/fg-magento-to-woocommerce/

== Installation ==

1.  Install the plugin in the Admin => Plugins menu => Add New => Upload => Select the zip file => Install Now
2.  Activate the plugin in the Admin => Plugins menu
3.  Run the importer in Tools > Import > Magento
4.  Configure the plugin settings. You can find the Magento database parameters in the Magento file app/etc/local.xml<br />
    Hostname = host<br />
    Port     = 3306 (standard MySQL port)<br />
    Database = dbname<br />
    Username = username<br />
    Password = password<br />
    Table prefix = table_prefix<br />

== Frequently Asked Questions ==

= I get the message: "[fg-magento-to-woocommerce] Couldn't connect to the Magento database. Please check your parameters. And be sure the WordPress server can access the Magento database. SQLSTATE[28000] [1045] Access denied for user 'xxx'@'localhost' (using password: YES)" =

* First verify your login and password to your Magento database.
* If Magento and WordPress are not installed on the same host, you can do this:
- export the Magento database to a SQL file (with phpMyAdmin for example)
- import this SQL file on the same database as WordPress
- run the migration by using WordPress database credentials (host, user, password, database) instead of the Magento ones in the plugin settings.

= The migration stops and I get the message: "Fatal error: Allowed memory size of XXXXXX bytes exhausted" or I get the message: "Internal server error" =

* First, deactivate all the WordPress plugins except the ones used for the migration
* You can run the migration again. It will continue where it stopped.
* You can add: `define('WP_MEMORY_LIMIT', '1G');` in your wp-config.php file to increase the memory allowed by WordPress
* You can also increase the memory limit in php.ini if you have write access to this file (ie: memory_limit = 1G).

= I get a blank screen and the import seems to be stopped =

* Same as above

= The media are not imported =

* Check the URL field that you filled in the plugin settings. It must be your Magento home page URL and must start with http://

= The media are not imported and I get the error message: "Warning: copy() [function.copy]: URL file-access is disabled in the server configuration" =

* The PHP directive "Allow URL fopen" must be turned on in php.ini to copy the medias. If your remote host doesn't allow this directive, you will have to do the migration on localhost.

= I get the message: "Fatal error: Class 'PDO' not found" =

* PDO and PDO_MySQL libraries are needed. You must enable them in php.ini on the WordPress host.<br />
Or on Ubuntu:<br />
sudo php5enmod pdo<br />
sudo service apache2 reload

= I get this error: PHP Fatal error: Undefined class constant 'MYSQL_ATTR_INIT_COMMAND' =

* You have to enable PDO_MySQL in php.ini on the WordPress host. That means uncomment the line extension=pdo_mysql.so in php.ini

= Does the migration process modify the Magento site it migrates from? =

* No, it only reads the Magento database.

= Do I need to keep the plugin activated after the migration? =

* No, you can deactivate or even uninstall the plugin after the migration.

= Is there a log file to show the information from the import? =
* Yes. First you must put these lines in wp-config.php:<br />
define('WP_DEBUG', true);<br />
define('WP_DEBUG_LOG', true);<br />
And the messages will be logged to wp-content/debug.log.


Don't hesitate to let a comment on the forum or to report bugs if you found some.
http://wordpress.org/support/plugin/fg-magento-to-woocommerce

== Screenshots ==

1. Parameters screen

== Translations ==
* English (default)
* French (fr_FR)
* other can be translated

== Changelog ==

= 2.4.0 =
* Fixed: CMS pages from all languages were imported
* Fixed: Notice: Undefined index: name
* Tweak: Refactor some functions to allow multilingual import by the WPML addon

= 2.3.0 =
* Fixed: PHP Notice: Object of class WP_Error could not be converted to int
* Fixed: Notice: Undefined index: url_key

= 2.2.0 =
* New: Import the product featured images

= 2.1.0 =
* Fixed: Display an error message when the process hangs
* Tweak: Increase the speed of counting the terms
* Tested with WordPress 4.5.3

= 2.0.0 =
* New: Run the import in AJAX
* New: Add a progress bar
* New: Add a logger frame to see the logs in real time
* New: Ability to stop the import
* New: Compatible with PHP 7
* New: Compatible with WooCommerce 2.6.0

= 1.13.4 =
* Fixed: The products without stock were not imported

= 1.13.2 =
* Fixed: Products belonging to several bundles were imported as duplicates

= 1.13.1 =
* Fixed: Some descriptions were not imported correctly
* Tested with WordPress 4.5.2

= 1.12.1 =
* Fixed: Compatibility issues with Magento 1.3
* Tested with WordPress 4.5.1

= 1.12.0 =
* Tested with WordPress 4.5

= 1.10.2 =
* Fixed: Notice: Undefined index: short_description
* Fixed: Column 'post_excerpt' cannot be null

= 1.10.1 =
* Fixed: Products not imported. Error: "WordPress database error Column 'post_content' cannot be null"

= 1.9.1 =
* Tested with WordPress 4.4.2

= 1.8.4 =
* Tested with WordPress 4.4.1

= 1.8.2 =
* Fixed: Fatal error: Call to undefined function add_term_meta()

= 1.8.1 =
* Fixed: Better clean the taxonomies cache

= 1.8.0 =
* Tweak: Optimize the termmeta table

= 1.7.0 =
* Tweak: Use the WordPress 4.4 term metas

= 1.6.1 =
* Tested with WordPress 4.4

= 1.6.0 =
* New: Compatibility with Magento 1.3

= 1.5.0 =
* New: Add a link to the FAQ in the connection error message

= 1.4.0 =
* New: Add an Import link on the plugins list page
* New: Change the translation domain name to be compliant with the WordPress translation system
* Tweak: Code refactoring

= 1.3.1 =
* Fixed: Refresh the display of the product categories
* Fixed: Error: 1054 Unknown column 'e.store_id' in 'where clause'

= 1.2.1 =
* Fixed: Duplicate images
* Fixed: Avoid a double slash in the media filename
* Fixed: Import the original category name instead of the translation
* Fixed: Notice: Undefined index: url_key

= 1.2.0 =
* New: Compatible with Magento 1.4 to 1.9
* New: Support the table prefix
* Fixed: Don't import the child products as single products

= 1.1.0 =
* Tweak: Change the range of get_attribute_values()
* Tweak: Make the argument $required_attributes optional
* Premium version released

= 1.0.1 =
* Tested with WordPress 4.3.1

= 1.0.0 =
* Initial version: Import Magento product categories, products, images and CMS

== Upgrade Notice ==

= 2.4.0 =
Fixed: CMS pages from all languages were imported
Fixed: Notice: Undefined index: name
Tweak: Refactor some functions to allow multilingual import by the WPML addon

= 2.3.0 =
Fixed: PHP Notice: Object of class WP_Error could not be converted to int
Fixed: Notice: Undefined index: url_key

= 2.2.0 =
New: Import the product featured images

= 2.1.0 =
Fixed: Display an error message when the process hangs
Tweak: Increase the speed of counting the terms
Tested with WordPress 4.5.3

= 2.0.0 =
New: Run the import in AJAX
New: Add a progress bar
New: Add a logger frame to see the logs in real time
New: Ability to stop the import
New: Compatible with PHP 7
New: Compatible with WooCommerce 2.6.0
