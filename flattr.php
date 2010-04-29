<?php
/*
Plugin Name: Flattr
Plugin URI: http://api.flattr.com/plugins/
Description: Give your readers the opportunity to Flattr your effort
Version: 0.7
Author: Flattr.com
Author URI: http://flattr.com/
*/

// Defines

define(FLATTR_WP_VERSION, '0.7');
define(FLATTR_WP_SCRIPT,  'http://api.flattr.com/button/load.js');

$flattr_categorys = array('text', 'images', 'audio', 'video', 'software', 'rest');

// Init

if (is_admin())
{
	add_action('admin_menu', 'flattr_add_meta_box');
	add_action('admin_menu', 'flattr_admin_menu');
	add_action('admin_init', 'flattr_admin_init' );
	add_action('save_post', 'flattr_save_post');
}

if (get_option('flattr_aut', 'on') == 'on')
{
	remove_filter('get_the_excerpt', 'wp_trim_excerpt');
	add_filter('get_the_excerpt', create_function('$content', 'remove_filter("the_content", "flattr_the_content"); return $content;'), 9);
	add_filter('get_the_excerpt', create_function('$content', 'add_filter("the_content", "flattr_the_content"); return $content;'), 11);
	add_filter('the_content', 'flattr_the_content'); 
}


// Admin methods

function flattr_save_post( $id )
{
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
	{
		return $id;
	}

	if ( ! current_user_can('edit_post', $id) )
	{
		return $id;
	}

	add_post_meta($id, '_flattr_post_language', $_POST['flattr_post_language'], true) or update_post_meta($id, '_flattr_post_language', $_POST['flattr_post_language']);
	add_post_meta($id, '_flattr_post_category', $_POST['flattr_post_category'], true) or update_post_meta($id, '_flattr_post_category', $_POST['flattr_post_category']);
	return true;
}

function flattr_add_meta_box()
{
	if ( function_exists('add_meta_box') )
	{
		add_meta_box('flattr_post_settings', __('Flattr settings'), 'flattr_inner_meta_box', 'post', 'advanced');
	}
	else
	{
		add_action('dbx_post_advanced', 'flattr_old_meta_box');
	}
}

function flattr_old_meta_box()
{
?>
<div class="dbx-b-ox-wrapper">
	<fieldset id="flattr_fieldsetid" class="dbx-box">
		<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">Flattr settings</h3></div>
		<div class="dbx-c-ontent-wrapper">
			<div class="dbx-content">
				<?php flattr_inner_meta_box() ?>
			</div>
		</div>
	</fieldset>
</div>
<?php
}

function flattr_inner_meta_box()
{
	global $post;
	$selectedLanguage = get_post_meta($post->ID, '_flattr_post_language', true);
	$selectedCategory = get_post_meta($post->ID, '_flattr_post_category', true);

	if ( ! $selectedLanguage )
	{
		$selectedLanguage = get_option('flattr_lng');
	}
	if ( ! $selectedCategory )
	{
		$selectedCategory = get_option('flattr_cat');
	}
?>
<label for="flattr_post_language"><?php echo __('Language:') ?></label>
<select name="flattr_post_language" id="flattr_post_language">
<?php foreach ( get_flattr_languages() as $languageCode => $language ): ?>
	 <option value="<?php echo $languageCode?>"<?php echo ($languageCode == $selectedLanguage) ? ' selected' : ''; ?>><?php echo $language ?></option>
<?php endforeach ?>
</select>
<br />
<label for="flattr_post_category"><?php echo __('Category:') ?></label>
<select name="flattr_post_category" id="flattr_post_category">
<?php foreach ( get_flattr_categorys() as $category ): ?>
	 <option value="<?php echo $category?>"<?php echo ($category == $selectedCategory) ? ' selected' : ''; ?>><?php echo ucfirst($category) ?></option>
<?php endforeach ?>
</select>

<?php
}

function flattr_admin_init()
{
	register_setting('flattr-settings-group', 'flattr_uid');
	register_setting('flattr-settings-group', 'flattr_cat');
	register_setting('flattr-settings-group', 'flattr_aut');
	register_setting('flattr-settings-group', 'flattr_lng');
}

function flattr_admin_menu()
{
	add_options_page('Flattr', 'Flattr', 8, basename(__FILE__), 'flattr_settings_page');
}

function flattr_permalink($userID, $category, $title, $description, $tags, $url, $language)
{
	$output = "<script type=\"text/javascript\">\n";
	$output .= "var flattr_wp_ver = '" . FLATTR_WP_VERSION . "';\n";
	$output .= "var flattr_uid = '" . flattr_safe_output($userID)      . "';\n";
	$output .= "var flattr_cat = '" . flattr_safe_output($category)    . "';\n";
	$output .= "var flattr_tle = '" . flattr_safe_output($title)       . "';\n";
	$output .= "var flattr_dsc = '" . flattr_safe_output($description) . "';\n";
	$output .= "var flattr_tag = '" . flattr_safe_output($tags)        . "';\n";
	$output .= "var flattr_url = '" . flattr_safe_output($url)         . "';\n";
	$output .= "var flattr_lng = '" . flattr_safe_output($language)         . "';\n";
	$output .= "</script>";

	return $output . '<script src="' . FLATTR_WP_SCRIPT . '" type="text/javascript"></script>';
}

function flattr_safe_output($expression)
{
	return trim(preg_replace('~\r\n|\r|\n~', ' ', addslashes($expression)));
}

function flattr_settings_page()
{
	?>
	<div class="wrap">
		<h2>Flattr Settings</h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'flattr-settings-group' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Your Flattr user ID</th>
					<td><input name="flattr_uid" type="text" value="<?php echo(get_option('flattr_uid')); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Default category for your posts</th>
					<td>
						<select name="flattr_cat">
							<?php foreach ( get_flattr_categorys() as $category ): ?>
							<option value="<?php echo $category?>"<?php echo ($category == get_option('flattr_cat')) ? ' selected' : ''; ?>><?php echo $category?></option>
							<? endforeach ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Default language for your posts</th>
					<td>
						<select name="flattr_lng">
							<?php foreach ( get_flattr_languages() as $languageCode => $language ): ?>
							<option value="<?php echo $languageCode ?>"<?php echo ($languageCode == get_option('flattr_lng')) ? ' selected' : ''; ?>><?php echo $language ?></option>
							<?php endforeach ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Insert Flattr automagically</th>
					<td><input <?php if (get_option('flattr_aut', 'on') == 'on') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_aut" value="on" /><br />(uncheck this if you rather use <code>&lt;?php the_flattr_permalink() ?&gt;</code>)</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php
}

function get_flattr_categorys()
{
	global $flattr_categorys;
	return $flattr_categorys;
}

function flattr_the_content($content)
{
	$content .= get_the_flattr_permalink();
	return $content;
}


// User methods

function get_the_flattr_permalink()
{
	$uid = get_option('flattr_uid');

	global $post;
        $selectedLanguage = get_post_meta($post->ID, '_flattr_post_language', true);
        $selectedCategory = get_post_meta($post->ID, '_flattr_post_category', true);

        if ( ! $selectedLanguage )
        {
                $selectedLanguage = get_option('flattr_lng');
        }
        if ( ! $selectedCategory )
        {
                $selectedCategory = get_option('flattr_cat');
        }

	if (strlen($uid) && strlen($selectedCategory) && strlen($selectedLanguage))
	{
		return flattr_permalink($uid, $selectedCategory, get_the_title(), flattr_get_excerpt(), strip_tags(get_the_tag_list('', ',', '')), get_permalink(), $selectedLanguage );
	}
}

function flattr_get_excerpt( $excerpt_max_length = 1024 )
{
	global $post;
	$excerpt = $post->post_excerpt;
	if (! $excerpt)
	{
		$excerpt = $post->post_content;
    	}
       	$excerpt = strip_tags($excerpt);
       	$excerpt = strip_shortcodes( $excerpt );
       	$excerpt = str_replace(']]>', ']]&gt;', $excerpt);

	$excerpt = preg_replace('/httpvh:\/\/[^ ]+/', '', $excerpt);

    	// Try to shorten without breaking words
    	if ( strlen($excerpt) > $excerpt_max_length )
    	{
        	$pos = strpos($excerpt, ' ', $excerpt_max_length);
        	if ($pos !== false)
        	{
            		$excerpt = substr($excerpt, 0, $pos);
        	}
    	}

    	// If excerpt still too long
    	if ( strlen($excerpt) > $excerpt_max_length )
    	{
        	$excerpt = substr($excerpt, 0, $excerpt_max_length);
    	}
    	return $excerpt;
}


function the_flattr_permalink()
{
	echo(get_the_flattr_permalink());
}


// Deprecated methods

function FlattrDyn()
{
	$message = 'Deprecated function FlattrDyn() called.';
	trigger_error($message, E_USER_NOTICE);
	echo('<!-- ' . $message . ' -->');
}

function FlattrPerma()
{
	$message = 'Deprecated function FlattrPerma() called, use the_flattr_permalink() instead.';
	trigger_error($message, E_USER_NOTICE);
	echo('<!-- ' . $message . ' -->');
}

function get_flattr_languages() {
	$languages['sq_AL'] = 'Albanian';
	$languages['ar_DZ'] = 'Arabic';
	$languages['be_BY'] = 'Belarusian';
	$languages['bg_BG'] = 'Bulgarian';
	$languages['ca_ES'] = 'Catalan';
	$languages['zh_CN'] = 'Chinese';
	$languages['hr_HR'] = 'Croatian';
	$languages['cs_CZ'] = 'Czech';
	$languages['da_DK'] = 'Danish';
	$languages['nl_NL'] = 'Dutch';
	$languages['en_GB'] = 'English';
	$languages['et_EE'] = 'Estonian';
	$languages['fi_FI'] = 'Finnish';
	$languages['fr_FR'] = 'French';
	$languages['de_DE'] = 'German';
	$languages['el_GR'] = 'Greek';
	$languages['iw_IL'] = 'Hebrew';
	$languages['hi_IN'] = 'Hindi';
	$languages['hu_HU'] = 'Hungarian';
	$languages['is_IS'] = 'Icelandic';
	$languages['in_ID'] = 'Indonesian';
	$languages['ga_IE'] = 'Irish';
	$languages['it_IT'] = 'Italian';
	$languages['ja_JP'] = 'Japanese';
	$languages['ko_KR'] = 'Korean';
	$languages['lv_LV'] = 'Latvian';
	$languages['lt_LT'] = 'Lithuanian';
	$languages['mk_MK'] = 'Macedonian';
	$languages['ms_MY'] = 'Malay';
	$languages['mt_MT'] = 'Maltese';
	$languages['no_NO'] = 'Norwegian';
	$languages['pl_PL'] = 'Polish';
	$languages['pt_PT'] = 'Portuguese';
	$languages['ro_RO'] = 'Romanian';
	$languages['ru_RU'] = 'Russian';
	$languages['sr_RS'] = 'Serbian';
	$languages['sk_SK'] = 'Slovak';
	$languages['sl_SI'] = 'Slovenian';
	$languages['es_ES'] = 'Spanish';
	$languages['sv_SE'] = 'Swedish';
	$languages['th_TH'] = 'Thai';
	$languages['tr_TR'] = 'Turkish';
	$languages['uk_UA'] = 'Ukrainian';
	$languages['vi_VN'] = 'Vietnamese';

	return $languages;
}

?>
