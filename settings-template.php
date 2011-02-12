<?php

    define(FLATTRSS_PLUGIN_PATH, get_bloginfo('wpurl') . '/wp-content/plugins/flattr');

    include_once 'oAuth/flattr_rest.php';
    include_once 'oAuth/oauth.php';

    $server = $_SERVER["SERVER_NAME"];
    $server = preg_split("/:/", $server);
    $server = $server[0];

    ?>
<div class="wrap flattr-wrap" style="width:90%">
            <div>
            <!-- <h2><?php _e('Flattr Settings'); ?> <img id="loaderanim" onload="javascript:{document.getElementById('loaderanim').style.display='none'};" src="<?php echo get_bloginfo('wpurl') . '/wp-content/plugins/flattr'.'/img/loader.gif' ?>"/></h2> -->
<div class="tabber">
    <div style="float:right; margin-top: -31px;"><img src="../wp-content/plugins/flattr/img/flattr-logo-beta-small.png" alt="Flattr Beta Logo"/></div>
    <div class="tabbertab" title="Flattr Account" style="border-left:0;">
		<h2><?php _e('Basic Setup'); ?></h2>
                <p>
                    The basic account setup enables this plugin to work.
                </p>
                <table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Your Flattr account'); ?></th>
				<td>
					<?php
					$connect_callback = rawurlencode( (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
					if (get_option('flattr_uid')) { ?>
						Connected as
							<?php
							if (preg_match('/[A-Za-z-_.]/', get_option('flattr_uid'))) {
								?><a href="<?php echo esc_url( 'https://flattr.com/profile/' . get_option('flattr_uid') ); ?>"><?php
								esc_html_e(get_option('flattr_uid'));
								?></a>.<?php
							}
							else {
								?>user id <?php esc_html_e(get_option('flattr_uid'));?>.<?php
							}
							?>
						(<a href="https://flattr.com/login?idCallback=<?php echo $connect_callback; ?>">Reconnect</a>)
					<?php } else { ?>
						None - <a href="https://flattr.com/login?idCallback=<?php echo $connect_callback; ?>">Connect with Flattr</a>
					<?php } ?>
				</td>
			</tr>
		</table>
<?php if (get_option('flattr_uid')) { ?>
                <h2>Advanced Setup</h2>
                <p>
                    The advanced account setup enables advanced features like Feed buttons and autosubmit.
                </p>
<?php
    $oauth_token = get_option('flattrss_api_oauth_token');
    $oauth_token_secret = get_option('flattrss_api_oauth_token_secret');
    $flattrss_api_key = get_option('flattrss_api_key');
    $flattrss_api_secret = get_option('flattrss_api_secret');

    if ($oauth_token == $oauth_token_secret || $flattrss_api_key == $flattrss_api_secret) {
?>
      <ol>
          <li>Login to your Flattr Account at <a href="https://flattr.com/" target="_blank">flattr.com</a></li>
          <li>To get your personal Flattr APP Key and APP Secret you need to <a href="https://flattr.com/apps/new" target="_blank">register your blog</a> as Flattr app. <small><a href="http://developers.flattr.net/doku.php/register_your_application" target="_blank">(More Info)</a></small></li>
          <li>Choose reasonable values for <em>Application name</em>, <em>Application website</em> and <em>Application description</em></li>
          <li>It is mandatory to <strong>select BROWSER application type!</strong> This plugin will currently <strong>not work if CLIENT is selected</strong>.</li>
          <li>You must use <code><?php echo $server; ?></code> as callback domain.</li>
          <li>Copy 'n Paste your APP Key and APP Secret in the corresponding fields below. Save Changes.</li>
          <li>As soon as you saved your APP information <a href="#Authorize">authorize</a> your Flattr account with your own application.</li>
          <li>If everything is done correctly you'll see your <a href="#UserInfo">Flattr username and info</a> on this site.</li>
      </ol>
<?php } ?>
<form method="post" action="options.php">
<?php settings_fields( 'flattr-settings-group' ); ?>
    <table class="form-table">
            <tr valign="top">
                <th scope="row">Callback Domain</th>
                <td><input size="30" value="<?php echo $server; ?>" readonly/></td>
            </tr>
            <tr valign="top">
                <th scope="row">APP_KEY</th>
                <td><input size="70" name="flattrss_api_key" value="<?php echo get_option('flattrss_api_key') ?>"/></td>
            </tr>
            <tr valign="top">
                <th scope="row">APP_SECRET</th>
                <td><input size="70" name="flattrss_api_secret" value="<?php echo get_option('flattrss_api_secret') ?>"/></td>
            </tr>
    </table>
    <?php

    $api_key = get_option('flattrss_api_key');
    $api_secret = get_option('flattrss_api_secret');

    if ($api_key != $api_secret) {

    $flattr = new Flattr_Rest($api_key, $api_secret);

    # Do not rawurlencode!
    $callback_ = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;

    $token = $flattr->getRequestToken( $callback_ );
    $_SESSION['flattrss_current_token'] = $token;

    $url = $flattr->getAuthorizeUrl($token, 'read,readextended,click,publish');

        ?><a name="Authorize"><div id="icon-options-general" class="icon32"><br /></div><h2>Authorize App</h2></a>
        <p>In order to automatically generate the correct "<em>Things</em>" link for your blog post from the feed, you need to authorize you Flattr app with your Flattr account.</p>
          <p><a href="<?php echo $url;?>">(re-)Authorize with Flattr</a>.
<?php

                #print_r($flattr);

    $oauth_token = get_option('flattrss_api_oauth_token');
    $oauth_token_secret = get_option('flattrss_api_oauth_token_secret');

    if ($oauth_token != $oauth_token_secret) {
        $flattr_user = new Flattr_Rest($api_key, $api_secret, $oauth_token, $oauth_token_secret);
        if ( $flattr_user->error() ) {
            echo( 'Error ' . $flattr_user->error() );
        }
        $user = $flattr_user->getUserInfo();
?>
    <div style="float:right"><img src="<?php echo $user['gravatar'];?>"></div><a name="UserInfo"><h2><img src="<?php echo FLATTRSS_PLUGIN_PATH .'/img/flattr_button.png' ?>" alt="flattr"/>&nbsp;Advanced Flattr User Info</h2></a>
    <p><?php echo $user['firstname'];?>&nbsp;<?php echo $user['lastname'];?><br/>
    <?php echo $user['username'];?>(<?php echo $user['id'];?>)</p>
    <p>Flattr: <a href="https://flattr.com/profile/<?php echo $user['username'];?>" target="_blank">Profile</a>, <a href="https://flattr.com/dashboard" target="_blank">Dashboard</a>, <a href="https://flattr.com/settings" target="_blank">Settings</a></p>
        <?php
        #print_r($flattr_user);
    }
  }
}
?>
    </div>
    <div class="tabbertab" title="Post/Page Buttons">
		<h2>Post/Page Buttons</h2>
                <p>These options are for the Flattr buttons automatically generated for posts and pages.</p>
		
			<table class="form-table">

				<tr valign="top">
					<th scope="row"><?php _e('Default category for your posts'); ?></th>
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

				<tr valign="top">
					<th scope="row"><?php _e('Default language for your posts'); ?></th>
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

				<tr valign="top">
					<th scope="row"><?php _e('Insert button before the content'); ?></th>
					<td><input <?php if (get_option('flattr_top', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_top" value="true" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Use the compact button'); ?></th>
					<td><input <?php if (get_option('flattr_compact', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_compact" value="true" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Hide my posts from listings on flattr.com'); ?></th>
					<td><input <?php if (get_option('flattr_hide', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_hide" value="true" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Insert Flattr button into posts automagically'); ?></th>
					<td><input <?php if (get_option('flattr_aut', 'off') == 'on') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_aut" value="on" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Insert Flattr button into pages automagically'); ?></th>
					<td><input <?php if (get_option('flattr_aut_page', 'off') == 'on') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_aut_page" value="on" /></td>
				</tr>
                                <tr valign="top">
                                    <th scope="row" colspan="2">You can use <code>&lt;?php the_flattr_permalink() ?&gt;</code> in your template/theme to insert a flattr button
                                    </th>
                                </tr>

				<?php if ( function_exists('st_add_widget') ) { ?>
					<tr valign="top">
						<th scope="row"><?php _e('Override ShareThis widget'); ?></th>
						<td><input <?php if (get_option('flattr_override_sharethis', 'false') == 'true') { echo(' checked="checked"'); } ?> type="checkbox" name="flattr_override_sharethis" value="true" /><br />(will add the Flattr button after the ShareThis buttons)</td>
					</tr>
				<?php } ?>
			</table>
    </div>
    <div class="tabbertab">
            <h2>Advanced Settings</h2>
            <?php if (!function_exists('curl_init')) { ?>
            <p id="message" class="updated" style="padding:10px;"><strong>Attention:</strong>&nbsp;Currently nothing can be autosubmitted. Enable cURL extension for your webserver to use this feature!</p>
            
            <?php }?>
            <table>
                <tr valign="top">
                    <th scope="row">Automatic Submission</th>
                    <td><p><input name="flattrss_autosubmit" type="checkbox"<?php echo get_option('flattrss_autosubmit')? " checked": ""; echo ($oauth_token != $oauth_token_secret && get_option('flattr_hide') == false)? "":" disabled"; ?> />&nbsp;Check this box to automatically submit your blog post when you publish. You need to complete the full advanced setup in order for autosubmission to work.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Suppress Warnings</th>
                    <td><p><input name="flattrss_error_reporting" type="checkbox"<?php echo get_option('flattrss_error_reporting')? " checked": "" ?>/>&nbsp;This is an advanced option for supression of error messages upon redirect from feed to thing. Use with caution, as flattr things might be submitted incomplete. Incomplete things are subject to be hidden on the flattr homepage!<br>If in doubt, leave disabled.</p>
                    </td>
                </tr>
            </table>
            <h2>Feed Settings</h2>
            <?php if (!function_exists('curl_init')) { ?>
            <p id="message" class="updated" style="padding:10px;"><strong>Attention:</strong>&nbsp;Currently no button will be inserted in your RSS feed. Enable cURL extension for your webserver to use this feature.</p>
            <?php }?>
            <table>
                <tr valign="top">
                <th scope="row">Custom Image URL</th>
                <td><p>This image is served as static image to be included in the RSS/Atom Feed of your blog.</p><input name="flattrss_custom_image_url" size="70" value="<?php echo get_option('flattrss_custom_image_url');?>"/><br/>
                    <?php if ( get_option('flattrss_custom_image_url') != FLATTRSS_PLUGIN_PATH .'/img/flattr-badge-large.png') { ?>
                    Default Value:<br>
                    <input size="70" value="<?php echo FLATTRSS_PLUGIN_PATH .'/img/flattr-badge-large.png';?>" readonly><br />
                    <?php } ?>
                    Preview:<br>
                    <img src="<?php echo get_option('flattrss_custom_image_url');?>">
                    <p></p>
                </td>
                </tr>
            </table>
    </div>
    <div class="tabbertab">
        <h2>Expert Settings</h2>
        <p><strong>WARNING:</strong> Please do not change any value unless you are exactly sure of what you are doing! Settings made on this page will likely override every other behaviour.</p>
        <table>
            <tr valign="top">
                <th scope="row">Post Type</th>
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
        </table>
    </div>

    <div class="tabbertab" title="Feedback">
        <h2>Feedback</h2>
        <table>
            <tr>
                <td valign="top" style="padding-top:13px;padding-right: 13px;">
                    <script type="text/javascript">
        var flattr_uid = "der_michael";
        var flattr_tle = "Wordpress Flattr plugin";
        var flattr_dsc = "Give your readers the opportunity to Flattr your effort. See http://wordpress.org/extend/plugins/flattr/ for details.";
        var flattr_cat = "software";
        var flattr_tag = "wordpress,plugin,flattr,rss";
        var flattr_url = "http://wordpress.org/extend/plugins/flattr/";
    </script><script src="http://api.flattr.com/button/load.js" type="text/javascript"></script>
    <p><a href="https://flattr.com/donation/give/to/der_michael" style="color:#ffffff;text-decoration:none;background-image: url(https://flattr.com/_img/fluff/bg-boxlinks-green.png);border-radius:3px;text-shadow:#666666 0 1px 1px;width:53px;padding:1px;padding-top: 2px;padding-bottom: 2px;display:block;text-align:center;font-weight: bold;" target="_blank">Donate</a></p>
                </td>
                <td>
                    <p>Please post feedback regarding wordpress integration on <a href="http://wordpress.org/tags/flattr?forum_id=10" target="_blank">the plugins board at wordpress.org</a>. You can use <a href="http://forum.flattr.net/" target="_blank">the official flattr board</a> for every concern regarding flattr.</p>
                    <p>If you have a certain remark, request or simply something you want to let me know feel free to mail me at <a href="mailto:flattr@allesblog.de?subject=Flattr Wordpress Plugin" title="flattr@allesblog.de">flattr@allesblog.de</a>. Please note that I'm not an official part of the Flattr Dev-Team. So I can only answer questions regarding the flattr wordpress plugin alone.</p>
                    <p><strong>Spread the word!</strong></p>
                    <p>You can help getting Flattr out there!</p>
                </td>
            </tr>
        </table>
        <h2>Debug</h2>
        <p>
            Please provide the following information with your support request.
        </p>
        <textarea cols="80" rows="10"><?php

            if (time() - $_SESSION['debug_date']>60) {
                $_SESSION['debug_date'] = time();
                $_SESSION['debug'] = "";
                if (function_exists('apache_get_version')) {
                    $_SESSION['debug'] .= "HTTPSERVER: ".apache_get_version() ."\n";
                } elseif (function_exists('iis_start_server')) {
                    $_SESSION['debug'] .= "IIS Server\n";
                }
                if (function_exists('domxml_version')) {
                    $_SESSION['debug'] .=  "XML Version: ".domxml_version()." (PHP4!)\n";
                }
                if (defined('LIBXML_VERSION')) {
                    $_SESSION['debug'] .= "LIBXML_VERSION: ". LIBXML_VERSION ."\n";
                } else {
                    $modules = get_loaded_extensions();
                    foreach ($modules as $module) {
                        $_SESSION['debug'] .=  trim("$module ". phpversion($module)).", ";
                    }
                }
            }
            echo htmlentities($_SESSION['debug']);

        ?></textarea>
    </div>
    <p class="submit">
        <input type="submit" class="button-primary" value="Save Changes" />
        <input type="reset" class="button" value="Reset" />
    </p>
       		</form>
</div>
</div>

        </div><script type="text/javascript" src="<?php echo FLATTRSS_PLUGIN_PATH . '/tabber.js'; ?>"></script>