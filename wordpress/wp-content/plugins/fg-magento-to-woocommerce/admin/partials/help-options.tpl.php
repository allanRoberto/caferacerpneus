<div id="fgm2wc-help-options">
<h1>FG Magento to WooCommerce Options</h1>

<h2>Empty WordPress content</h2>
<p>Before running the import or if you want to rerun the import from scratch, you can empty the WordPress content.</p>
<p><strong>Remove only the new imported data:</strong> Only the new imported data will be removed when you click on the "Empty WordPress content" button.</p>
<p><strong>Remove all WordPress content:</strong> All the WordPress content (posts, pages, products, attachments, categories, tags, navigation menus, custom post types) will be removed when you click on the "Empty WordPress content" button.</p>
<p><strong>Automatic removal:</strong> If you check this option, all the WordPress content will be deleted when you click on the Import button.</p>


<h2>Magento web site parameters</h2>

<p><strong>URL:</strong> In this field, you fill in the Magento home page URL.</p>


<h2>Magento database parameters</h2>

<p>You can find the following informations in the Magento file <strong>app/etc/local.xml</strong></p>

<p><strong>Hostname:</strong> host</p>
<p><strong>Port:</strong> By default, it is 3306.</p>
<p><strong>Database:</strong> dbname</p>
<p><strong>Username:</strong> username</p>
<p><strong>Password:</strong> password</p>
<p><strong>Magento table prefix:</strong> prefix</p>


<h2>Behavior</h2>

<p><strong>Medias:</strong><br />
<ul>
<li><strong>Skip media:</strong> You can import or skip the medias (images, attached files).</li>
<li><strong>Import first image:</strong> You can import the first image contained in the article as the WordPress post featured image or just keep it in the content (as is), or to both.</li>
<li><strong>Import external media:</strong> If you want to import the medias that are not on your site, check the "External media" option. Be aware that it can reduce the speed of the import or even hang the import.</li>
<li><strong>Import media with duplicate names:</strong> If you have several images with the exact same filename in different directories, you need to check the "media with duplicate names" option. In this case, all the filenames will be named with the directory as a prefix.</li>
<li><strong>Force media import:</strong> If you already imported some images and these images are corrupted on WordPress (images with a size of 0Kb for instance), you can force the media import. It will overwrite the already imported images. In a normal use, you should keep this option unchecked.</li>
<li><strong>Don't include the first image into the product gallery:</strong> The product thumbnail image will be defined as the product feature image but will not belong to the product gallery.</li>
<li><strong>Timeout for each media:</strong> The default timeout to copy a media is 5 seconds. You can change it if you have many errors like "Can't copy xxx. Operation timeout".</li>
</ul>
</p>

<p><strong>Import prices:</strong> You can import the prices excluding tax or including tax. If you choose "including tax", you must define first a default tax in <a href="admin.php?page=wc-settings&tab=tax&section=standard" target="_blank">WooCommerce Tax tab</a>.</p>

<p><strong>Create pages:</strong> You have the choice to import the Magento CMS articles as WordPress posts or pages.</p>

<?php do_action('fgm2wc_help_options'); ?>

</div>
