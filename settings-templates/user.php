<form method="post" action="<?=get_bloginfo('wpurl') . '/wp-admin/users.php?page=flattr/flattr.php?user';?>">
<?php wp_nonce_field(); ?>
    <table>
<tr>
    <th><?php _e('Your Flattr Username'); ?></th>
    <td>
        <input name="user_flattr_uid" type="text" value="<?php echo(get_user_meta(get_current_user_id( ), "user_flattr_uid", true)); ?>" />
    </td>
</tr>
<tr>
    <th><?php _e('Default category for your posts'); ?></th>
    <td>
        <select name="user_flattr_cat">
            <?php
                foreach (Flattr::getCategories() as $category)
                {
                    printf('<option value="%1$s" %2$s>%1$s</option>',
                            $category,
                            ($category == get_user_meta(get_current_user_id( ), "user_flattr_cat", true) ? 'selected' : '')
                    );
                }
            ?>
        </select>
    </td>
</tr>

<tr>
    <th><?php _e('Default language for your posts'); ?></th>
    <td>
    <select name="user_flattr_lng">
        <?php
            foreach (Flattr::getLanguages() as $languageCode => $language)
            {
                printf('<option value="%s" %s>%s</option>',
                    $languageCode,
                    ($languageCode == get_user_meta(get_current_user_id( ), "user_flattr_lng", true) ? 'selected' : ''),
                    $language
                );
            }
        ?>
    </select>
</td>
</tr>

<tr>
    <th><?php _e('Authorize for Autosubmit'); ?></th>
    <td>
        <?php
            $key = get_option('flattrss_api_key', true);
            $sec = get_option('flattrss_api_secret', true);

            $callback = urlencode(home_url()."/wp-admin/admin.php?page=flattr/flattr.php");

            if (!is_null($key) && !is_null($sec)) {

                include_once dirname(__FILE__).'/../flattr_client.php';

                $client = new OAuth2Client(array_merge(array(
                    'client_id'         => $key,
                    'client_secret'     => $sec,
                    'base_url'          => 'https://api.flattr.com/rest/v2',
                    'site_url'          => 'https://flattr.com',
                    'authorize_url'     => 'https://flattr.com/oauth/authorize',
                    'access_token_url'  => 'https://flattr.com/oauth/token',

                    'redirect_uri'      => $callback,
                    'scopes'            => 'thing+flattr',
                    'token_param_name'  => 'Bearer',
                    'response_type'     => 'code',
                    'grant_type'        => 'authorization_code',
                    'access_token'      => null,
                    'refresh_token'     => null,
                    'code'              => null,
                    'developer_mode'    => false
                ))); 

                try {
                    $url = $client->authorizeUrl();
                    $text = "(re-)authorize";

                } catch (Exception $e) {
                    $text = "The admin needs to create and authorize a Flattr App first.";

                }
            } else {
                $text = "DEACTIVATED";
            }
        ?>
        <a href="<?=$url;?>"><?=$text;?></a>
    </td>
</tr>

<?php
    $token = get_user_meta( get_current_user_id() , "user_flattrss_api_oauth_token", true);
    
    if (!empty($token)) {
        $client = new OAuth2Client( array_merge(array(
            'client_id'         => $key,
            'client_secret'     => $sec,
            'base_url'          => 'https://api.flattr.com/rest/v2',
            'site_url'          => 'https://flattr.com',
            'authorize_url'     => 'https://flattr.com/oauth/authorize',
            'access_token_url'  => 'https://flattr.com/oauth/token',

            'redirect_uri'      => $callback,
            'scopes'            => 'thing+flattr',
            'token_param_name'  => 'Bearer',
            'response_type'     => 'code',
            'grant_type'        => 'authorization_code',
            'refresh_token'     => null,
            'code'              => null,
            'developer_mode'    => false,

            'access_token'      => $token,
        )));
    }
    
    try {
        $user = $client->getParsed('/user');
        
        if (!isset($user['error'])) {
?>
<tr>
    <th><?php _e('Authorized User'); ?></th>
    <td>
    <?=  '<img style="float:right;width:48px;height:48px;border:0;" src="'. $user['avatar'] .'"/>'.
         '<h3>'.$user['username'].'</h3>'.
         '<ul><li>If this is your name (and avatar) authentication was successfull.</li></ul>';?>
    </td>
</tr>
<?php
        } 
    } catch (Exception $e) {}