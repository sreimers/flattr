<form method="post" action="options.php">
<?php settings_fields( 'flattr-settings-group' ); ?>

<h3><?php _e('Basic settings');?></h3>

    <table class="form-table">
<tr>
    <th><label for="flattr_uid"><?php _e('Flattr Username'); ?></label></th>
    <td>
        <input id="flattr_uid" name="flattr_uid" type="text" value="<?php echo(esc_attr(get_option('flattr_uid'))); ?>" />
        <span class="description"><?php _e('The Flattr account to which the buttons will be assigned.'); ?></span>
    </td>
</tr>
<tr>
    <th><label for="flattr_atags"><?php _e('Additional Flattr tags for your posts'); ?></label></th>
    <td>
        <input id="flattr_atags" name="flattr_atags" type="text" value="<?php echo(esc_attr(get_option('flattr_atags', 'blog'))); ?>" />
        <span class="description"><?php _e("Comma separated list of additional tags to use in Flattr buttons"); ?></span>
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
    <th><?php _e('Hide my posts from listings on Flattr.com'); ?></th>
    <td>
        <input <?php if (get_option('flattr_hide', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_hide" value="true" />
        <span class="description"><?php _e("If your content could be considered offensive then you're encouraged to hide it."); ?></span>
    </td>
</tr>

</table>

<h3><?php _e('Advanced settings');?></h3>

<table class="form-table">
    <tr>
        <th><?php _e('User Specific Buttons'); ?></th>
        <td>
            <label><input type="checkbox" name="user_based_flattr_buttons"<?php echo get_option('user_based_flattr_buttons')?" checked":"";?> /> <?php _e("If you tick this box, every user of the blog will have the chance to register its own Flattr buttons. Buttons will then be linked to post authors and only display if the user completed plugin setup."); ?></label>
        </td>
    </tr>

    <tr>
        <th><?php _e('Post Types'); ?></th>
        <td>
            <ul>
            <?php $types = get_post_types();
                  $flattr_post_types = (array)get_option('flattr_post_types', array());
                foreach ($types as $type) {
                    $selected = (is_array($flattr_post_types) && in_array($type, $flattr_post_types))? " checked" : "";
                    echo "<li><input name=\"flattr_post_types[]\" value=\"$type\" type=\"checkbox\"$selected/>&nbsp;$type</li>";
                }
            ?></ul>
            <span class="description"><?php _e('Appends Flattr Button only to selected post types.'); ?></span>
        </td>
    </tr>

<tr>
    <th><?php _e("Add button before post's content"); ?></th>
    <td><input <?php if (get_option('flattr_top', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_top" value="true" /></td>
</tr>

<tr>
    <th>Button type</th>
    <td>
        <ul>
            <li>
                <input type="radio" name="flattr_button_style" value="js"<?=(get_option('flattr_button_style')=="js")?" checked":"";?>/>
                <script type="text/javascript">
                    var flattr_uid = "der_michael";
                    var flattr_btn = "<?=get_option('flattr_compact')?"compact":"";?>";
                    var flattr_tle = "Wordpress Flattr plugin";
                    var flattr_dsc = "Give your readers the opportunity to Flattr your effort. See http://wordpress.org/extend/plugins/flattr/ for details.";
                    var flattr_cat = "software";
                    var flattr_tag = "wordpress,plugin,flattr,rss";
                    var flattr_url = "http://wordpress.org/extend/plugins/flattr/";
                </script>
                <script src="<?php echo (isset($_SERVER['HTTPS'])) ? 'https' : 'http'; ?>://api.flattr.com/button/load.js" type="text/javascript"></script>
                <span class="description"><?php _e('Dynamic javascript version'); ?></span>
            </li>
            <li>
                <input type="radio" name="flattr_button_style" value="image"<?=(get_option('flattr_button_style')=="image")?" checked":"";?>/>
                <img src="<?=get_option('flattrss_custom_image_url');?>"/>
                <span class="description"><?php _e('Static image version'); ?></span>
            </li>
            <li>
                <input type="radio" name="flattr_button_style" value="text"<?=(get_option('flattr_button_style')=="text")?" checked":"";?>/>
                <a href="#">Flattr this!</a>
                <span class="description"><?php _e('Static text version'); ?></span>
            </li>
        </ul>
    </td>
</tr>

<tr>
    <th><?php _e('Use the compact button'); ?></th>
    <td>
        <input <?php if (get_option('flattr_compact', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_compact" value="true" />
        <span class="description"><?php _e('Only applies to the javascript button type.'); ?></span>
    </td>
</tr>

<tr>
<th>Custom Image URL</th>
<td><input type="text" name="flattrss_custom_image_url" size="70" value="<?php echo esc_attr(get_option('flattrss_custom_image_url'));?>"/><br/>
    <?php if ( get_option('flattrss_custom_image_url') != get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png') { ?>
    Default Value:<br>
    <input type="text" size="70" value="<?php echo get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png';?>" readonly><br />
    <?php } ?>
    <span class="description"><?php _e('Only applies to the static image button type and the feed buttons.'); ?></span>
</td>
</tr>

<tr>
<th>Presubmit to Flattr Catalog</th>
<td>
    <span id="autosubmit" class="inactive">DEACTIVATED</span>
    <p class="description"><?php _e('Only use if you for some reason want to presubmit content to Flattr.com prior to them being flattred.'); ?></p>
</td>
</tr>

<tr>
    <th scope="row"><?php _e('Include in RSS/Atom feeds'); ?></th>
    <td>
        <input name="flattrss_button_enabled" type="checkbox" <?php if(get_option('flattrss_button_enabled')) {echo "checked";}?> />
        <span class="description">If selected, both static graphical Flattr buttons and <a href="http://developers.flattr.net/feed/">Flattr feed links</a> will be included in the RSS/Atom feeds.</span>
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