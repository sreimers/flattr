<?php

if ( isset ($_REQUEST['oauth_token']) && isset ($_REQUEST['oauth_verifier'])) {

    global $current_user;
    get_currentuserinfo();

    if ($current_user->user_level <  8) { //if not admin, die with message
            wp_die( __('You are not allowed to access this part of the site') );
    }

    if (session_id() == '') { session_start(); }

    include_once "oAuth/oauth.php";
    include_once "oAuth/flattr_rest.php";

    $api_key = get_option('flattrss_api_key');
    $api_secret = get_option('flattrss_api_secret');

    $flattr = new Flattr_Rest($api_key, $api_secret, $_SESSION['flattrss_current_token']['oauth_token'], $_SESSION['flattrss_current_token']['oauth_token_secret']);

    $access_token = $flattr->getAccessToken($_REQUEST['oauth_verifier']);

    if ($flattr->http_code == 200) {

        add_option('flattrss_api_oauth_token', $access_token['oauth_token']);
        update_option('flattrss_api_oauth_token', $access_token['oauth_token']);

        add_option('flattrss_api_oauth_token_secret', $access_token['oauth_token_secret']);
        update_option('flattrss_api_oauth_token_secret', $access_token['oauth_token_secret']);
    }

    header("Status: 307");
    header("Location: ". get_bloginfo('wpurl') .'/wp-admin/admin.php?page=flattr/settings.php');

    exit(307);
 }