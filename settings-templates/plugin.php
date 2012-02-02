<form method="post" action="options.php">
<?php settings_fields( 'flattr-settings-group' ); ?>
    <table>
<tr>
    <th><?php _e('User Specific Buttons'); ?></th>
    <td>
        <input type="checkbox" name="user_based_flattr_buttons"<?php echo get_option('user_based_flattr_buttons')?" checked":"";?> />&nbsp;If you tick this box, every user of the blog will have the chance to register it's own Flattr buttons. Buttons will then be linked to post authors and only display if the user completed plugin setup.
    </td>
</tr>

<tr>
    <th><?php _e('Flattr Account ID for the Blog'); ?></th>
    <td>
        <input name="flattr_uid" type="text" value="<?php echo(get_option('flattr_uid')); ?>" />
    </td>
</tr>
<tr>
    <th>Post Type</th>
    <td><p>Append Flattr Button only to selected post types.</p><ul>
        <?php $types = get_post_types();
              $flattr_post_types = get_option('flattr_post_types');
            foreach ($types as $type) {
                $selected = (is_array($flattr_post_types) && in_array($type, $flattr_post_types))? " checked" : "";
                echo "<li><input name=\"flattr_post_types[]\" value=\"$type\" type=\"checkbox\"$selected/>&nbsp;$type</li>";
            }
        ?></ul>
    </td>
</tr>
<tr>
    <th><?php _e('Default category for your posts'); ?></th>
    <td>
        <select name="flattr_cat">
            <?php
                foreach (Flattr::getCategories() as $category)
                {
                    printf('<option value="%1$s" %2$s>%1$s</option>',
                            $category,
                            ($category == get_option('flattr_cat') ? 'selected' : '')
                    );
                }
            ?>
        </select>
    </td>
</tr>
<tr>
    <th><?php _e('Default language for your posts'); ?></th>
    <td>
        <select name="flattr_lng">
            <?php
                foreach (Flattr::getLanguages() as $languageCode => $language)
                {
                    printf('<option value="%s" %s>%s</option>',
                            $languageCode,
                            ($languageCode == get_option('flattr_lng') ? 'selected' : ''),
                            $language
                    );
                }
            ?>
        </select>
    </td>
</tr>
<tr>
    <th><?php _e('Insert button before the content'); ?></th>
    <td><input <?php if (get_option('flattr_top', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_top" value="true" /></td>
</tr>

<tr>
    <th><?php _e('Use the compact button'); ?></th>
    <td><input <?php if (get_option('flattr_compact', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_compact" value="true" /></td>
</tr>

<tr>
    <th><?php _e('Hide my posts from listings on flattr.com'); ?></th>
    <td><input <?php if (get_option('flattr_hide', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_hide" value="true" /></td>
</tr>
<tr>
    <th>JavaScript Version</td>
    <td><input type="radio" name="flattr_button_style" value="js"<?=(get_option('flattr_button_style')=="js")?" checked":"";?>/><script type="text/javascript">
            var flattr_uid = "der_michael";
            var flattr_btn = "<?=get_option('flattr_compact')?"compact":"";?>";
            var flattr_tle = "Wordpress Flattr plugin";
            var flattr_dsc = "Give your readers the opportunity to Flattr your effort. See http://wordpress.org/extend/plugins/flattr/ for details.";
            var flattr_cat = "software";
            var flattr_tag = "wordpress,plugin,flattr,rss";
            var flattr_url = "http://wordpress.org/extend/plugins/flattr/";
        </script><script src="<?php echo (isset($_SERVER['HTTPS'])) ? 'https' : 'http'; ?>://api.flattr.com/button/load.js" type="text/javascript"></script>
    </td>
</tr><tr>
    <th>static Image</td>
    <td><input type="radio" name="flattr_button_style" value="image"<?=(get_option('flattr_button_style')=="image")?" checked":"";?>/>
        <img src="<?=get_option('flattrss_custom_image_url');?>"/>
    </td>
    
</tr><tr>
    <th>static Text</td>
    <td><input type="radio" name="flattr_button_style" value="text"<?=(get_option('flattr_button_style')=="text")?" checked":"";?>/>
    <a href="#">Flattr this!</a></td>
    
</tr>

<tr>
<th>Custom Image URL</th>
<td><input name="flattrss_custom_image_url" size="70" value="<?php echo get_option('flattrss_custom_image_url');?>"/><br/>
    <?php if ( get_option('flattrss_custom_image_url') != get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png') { ?>
    Default Value:<br>
    <input size="70" value="<?php echo get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png';?>" readonly><br />
    <?php } ?>
</td>
</tr>

<tr>
<th>Autosubmit to Flattr Catalog</th>
<td><span id="autosubmit" class="inactive">DEACTIVATED</span></td>
</tr>

<tr>
    <th scope="row">RSS/Atom Feed Button</th>
    <td><p><input name="flattrss_button_enabled" type="checkbox" <?php if(get_option('flattrss_button_enabled')) {echo "checked";}?> />&nbsp;A Flattr button will be included in the RSS/Atom Feed of your blog.</p>
    </td>
</tr>

<tr>
    <th scope="row"><?php _e('Insert Flattr button into posts automagically'); ?></th>
    <td><input <?php if (get_option('flattr_aut', 'off') == 'on') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_aut" value="on" /></td>
</tr>

<tr>
    <th scope="row"><?php _e('Insert Flattr button into pages automagically'); ?></th>
    <td><input <?php if (get_option('flattr_aut_page', 'off') == 'on') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_aut_page" value="on" /></td>
</tr>
<tr>
    <th scope="row" colspan="2">You can use <code>&lt;?php the_flattr_permalink() ?&gt;</code> in your template/theme to insert a flattr button
    </th>
</tr>