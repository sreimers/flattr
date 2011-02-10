<?php

if (isset ($_GET['id'])&&
        isset ($_GET['md5'])&&
        isset ($_GET['flattrss_redirect'])) {

    $e = error_reporting();
    if (get_option('flattrss_error_reporting')) {
        error_reporting(0);
    }

    $old_charset = ini_get('default_charset');
    ini_set('default_charset',get_option('blog_charset'));

    $id = intval($_GET['id']);
    $md5 = $_GET['md5'];

    $post = get_post($id,ARRAY_A);

    function return_error($x) { die(strval($x)); }

    if (md5($post['post_title']) != $md5) {
        return_error("Post title mismatch");
    }

    if ($post['post_status'] != "publish") {
        return_error("Post status not published");
    }

    if (get_option('flattrss_clicktrack_enabled')) {
        update_option('flattrss_clickthrough_n', get_option('flattrss_clickthrough_n')+1);
    }

    $url = get_permalink($post['ID']);
    $tagsA = get_the_tags($post['ID']);
    $tags = "";

    if ($tagsA) {
        foreach ($tagsA as $tag) {
            if (strlen($tags)!=0){
                $tags .=",";
            }
            $tags .= $tag->name;
        }
    }

    if (trim($tags) == "") {
        $tags .= "blog";
    }

    $category = "text";
    if (get_option('flattr_cat')!= "") {
        $category = get_option('flattr_cat');
    }

    $language = "en_EN";
    if (get_option('flattr_lng')!="") {
        $language = get_option('flattr_lng');
    }

    function getExcerpt($post, $excerpt_max_length = 1024) {

	$excerpt = $post['post_excerpt'];
	if (trim($excerpt) == "") {
        	$excerpt = $post['post_content'];
	}

        $excerpt = strip_shortcodes($excerpt);
        $excerpt = strip_tags($excerpt);
        $excerpt = str_replace(']]>', ']]&gt;', $excerpt);

        // Hacks for various plugins
        $excerpt = preg_replace('/httpvh:\/\/[^ ]+/', '', $excerpt); // hack for smartyoutube plugin
        $excerpt = preg_replace('%httpv%', 'http', $excerpt); // hack for youtube lyte plugin

        // Try to shorten without breaking words
        if ( strlen($excerpt) > $excerpt_max_length ) {
            $pos = strpos($excerpt, ' ', $excerpt_max_length);
            if ($pos !== false) {
                    $excerpt = substr($excerpt, 0, $pos);
            }
        }

        // If excerpt still too long
        if (strlen($excerpt) > $excerpt_max_length) {
            $excerpt = substr($excerpt, 0, $excerpt_max_length);
        }

        return $excerpt;
    }

    $content = preg_replace(array('/\<br\s*\/?\>/i',"/\n/","/\r/", "/ +/"), " ", getExcerpt($post));
    $content = strip_tags($content);

    if (strlen(trim($content)) == 0) {
        $content = "(no content provided...)";
    }

    $title = strip_tags($post['post_title']);
    $title = str_replace(array("\"","\'"), "", $title);

    include_once 'oAuth/flattr_rest.php';
    require_once 'oAuth/oauth.php';

    $api_key = get_option('flattrss_api_key');
    $api_secret = get_option('flattrss_api_secret');
    $oauth_token = get_option('flattrss_api_oauth_token');
    $oauth_token_secret = get_option('flattrss_api_oauth_token_secret');

    $flattr_user = new Flattr_Rest($api_key, $api_secret, $oauth_token, $oauth_token_secret);

    if ($flattr_user->error()) {
        return_error("Flattr User Error!");
    }

    function encode($string) {
        if (function_exists("mb_detect_encoding")) {
            $string = (mb_detect_encoding($string, "UTF-8") == "UTF-8" )? $string : utf8_encode($string);
        } else {
            $string = utf8_encode($string);
        }
        return $string;
    }

    if (get_option('flattrss_autodonate') && !isset($_SESSION['flattrss_autodonate_click'])) {
        $flattr_user->clickThing("ead246fc95fc401ce69d15f3981da971");
        $_SESSION['flattrss_autodonate_click'] = true;
    }

    $thing = $flattr_user->submitThing($url, encode($title), $category, encode($content), $tags, $language);

    if($flattr_user->http_code == 500) {
        /*
        header('Status-Code: 307');
        header('LOCATION: '.$url);
         */
        /*
        print_r(array($url, encode($title), $category, encode($content), $tags, $language));
        print_r($flattr_user);
        print_r($thing);
        die();

        break;
         */
    }

    if (isset ($thing['int_id'])) {
        header('LOCATION: https://flattr.com/thing/'.$thing['int_id']);
    }

    $thingList = $flattr_user->getThingList();
    $thing_id = 0;

    foreach ($thingList as $thing){
        if($thing['url'] == $url) {
            $thing_id = $thing['int_id'];
        }
    }

    $location = $url;

    if ($thing_id != 0) {
        $location = 'https://flattr.com/thing/'.$thing_id;
    }

    header('Status-Code: 307');
    header('LOCATION: '. $location);

    ini_set('default_charset',$old_charset);

    error_reporting($e);

    exit ($thing_id);
}