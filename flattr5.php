<?php

if (session_id() == '') { session_start(); }

class Flattr
{
	const VERSION = '0.9.22';
	const WP_MIN_VER = '2.9';
	const API_SCRIPT  = 'api.flattr.com/js/0.6/load.js?mode=auto';

	/** @var array */
	protected static $categories = array('text', 'images', 'audio', 'video', 'software', 'rest');
	/** @var array */
	protected static $languages;
	/** @var Flattr */
	protected static $instance;

	/** @var Flattr_Settings */
	protected $settings;

	/** @var String */
	protected $basePath;

	public function __construct()
	{	
		if (is_admin())
		{
			if (!$this->compatibilityCheck())
			{
				return;
			}
			
			$this->init();
		}
		if (( get_option('flattr_aut_page', 'off') == 'on' || get_option('flattr_aut', 'off') == 'on' ) && !in_array( 'live-blogging/live-blogging.php' , get_option('active_plugins') ))
		{
			remove_filter('get_the_excerpt', 'wp_trim_excerpt');

                        add_filter('the_content', array($this, 'injectIntoTheContent'),11);
                        add_filter('get_the_excerpt', array($this, 'filterGetExcerpt'), 1);
			if ( get_option('flattr_override_sharethis', 'false') == 'true' ) {
				add_action('plugins_loaded', array($this, 'overrideShareThis'));
			}
		}

		wp_enqueue_script('flattrscript', ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://' ) . self::API_SCRIPT, array(), '0.6', true);
	}

	function overrideShareThis() {
		if ( remove_filter('the_content', 'st_add_widget') || remove_filter('the_excerpt', 'st_add_widget') ) {
			add_filter('flattr_button', array($this, 'overrideShareThisFilter'));
		}
	}

	protected function addAdminNoticeMessage($msg)
	{
		if (!isset($this->adminNoticeMessages))
		{
			$this->adminNoticeMessages = array();
			add_action( 'admin_notices', array(&$this, 'adminNotice') );
		}
		
		$this->adminNoticeMessages[] = $msg;
	}
	
	public function adminNotice()
	{
		echo '<div id="message" class="error">';
		
		foreach($this->adminNoticeMessages as $msg)
		{
			echo "<p>{$msg}</p>";
		}
		
		echo '</div>';
	}

	protected function compatibilityCheck()
	{
		global $wp_version;
		
		if (version_compare($wp_version, self::WP_MIN_VER, '<'))
		{
			$this->addAdminNoticeMessage('<strong>Warning:</strong> The Flattr plugin requires WordPress '. self::WP_MIN_VER .' or later. You are currently using '. $wp_version);
			return false;
		}
		
		return true;
	}

	public function getBasePath()
	{
		if (!isset($this->basePath))
		{
			$this->basePath = WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/';
		}
		
		return $this->basePath;
	}

	public function getButton($skipOptionCheck = false)
	{
		global $post;

		if ( ! $skipOptionCheck && ( ($post->post_type == 'page' && get_option('flattr_aut_page', 'off') != 'on') || ($post->post_type != 'page' && get_option('flattr_aut', 'off') != 'on') || is_feed() ) )
		{
			return '';
		}

		if (get_post_meta($post->ID, '_flattr_btn_disabled', true))
		{
			return '';
		}

		$flattr_uid = get_option('flattr_uid');
		if (!$flattr_uid) {
			return '';
		}

		$selectedLanguage = get_post_meta($post->ID, '_flattr_post_language', true);
		if (empty($selectedLanguage))
		{
			$selectedLanguage = get_option('flattr_lng');
		}

		$selectedCategory = get_post_meta($post->ID, '_flattr_post_category', true);
		if (empty($selectedCategory))
		{
			$selectedCategory = get_option('flattr_cat');
		}

		$hidden = get_post_meta($post->ID, '_flattr_post_hidden', true);
		if ($hidden == '')
		{
			$hidden = get_option('flattr_hide', false);
		}

		$buttonData = array(

			'user_id'	=> $flattr_uid,
			'url'		=> get_permalink(),
			'compact'	=> ( get_option('flattr_compact', false) ? true : false ),
			'hide'		=> $hidden,
			'language'	=> $selectedLanguage,
			'category'	=> $selectedCategory,
			'title'		=> strip_tags(get_the_title()),
			'body'		=> strip_tags(preg_replace('/\<br\s*\/?\>/i', "\n", $this->getExcerpt())),
			'tag'		=> strip_tags(get_the_tag_list('', ',', ''))

		);

		if (isset($buttonData['user_id'], $buttonData['url'], $buttonData['language'], $buttonData['category']))
		{
			return $this->getButtonCode($buttonData);
		}
	}

	protected function getButtonCode($params)
	{
		$rev = sprintf('flattr;uid:%s;language:%s;category:%s;',
			$params['user_id'],
			$params['language'],
			$params['category']
		);

		if (!empty($params['tag']))
		{
			$rev .= 'tags:'. addslashes($params['tag']) .';';
		}

		if ($params['hide'])
		{
			$rev .= 'hidden:1;';
		}

		if ($params['compact'])
		{
			$rev .= 'button:compact;';
		}

		if (empty($params['body']) && !in_array($params['category'], array('images', 'video', 'audio')))
		{
			$params['body'] = get_bloginfo('description');

			if (empty($params['body']) || strlen($params['body']) < 5)
			{
				$params['body'] = $params['title'];
			}
		}

		return sprintf('<a class="FlattrButton" style="display:none;" href="%s" title="%s" rev="%s">%s</a>',
			$params['url'],
			addslashes($params['title']),
			$rev,
			$params['body']
		);
	}

	public static function getCategories()
	{
		return self::$categories;
	}

	public static function filterGetExcerpt($content)
	{
            $excerpt_length = apply_filters('excerpt_length', 55);
            $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');

            return self::getExcerpt($excerpt_length, $excerpt_more);
	}

	public static function getExcerpt($excerpt_max_length = 55, $excerpt_more = ' [...]')
	{
		global $post;
		
		$excerpt = $post->post_excerpt;
		if (! $excerpt)
		{
			$excerpt = $post->post_content;
	    }

		$excerpt = strip_shortcodes($excerpt);
		$excerpt = strip_tags($excerpt);
		$excerpt = str_replace(']]>', ']]&gt;', $excerpt);
		
		// Hacks for various plugins
		$excerpt = preg_replace('/httpvh:\/\/[^ ]+/', '', $excerpt); // hack for smartyoutube plugin
		$excerpt = preg_replace('%httpv%', 'http', $excerpt); // hack for youtube lyte plugin

            $excerpt = explode(' ', $excerpt, $excerpt_max_length);
              if ( count($excerpt) >= $excerpt_max_length) {
                array_pop($excerpt);
                $excerpt = implode(" ",$excerpt).' ...';
              } else {
                $excerpt = implode(" ",$excerpt);
              }
              $excerpt = preg_replace('`\[[^\]]*\]`','',$excerpt);

	    // Try to shorten without breaking words
	    if ( strlen($excerpt) > 1024 )
	    {
			$pos = strpos($excerpt, ' ', 1024);
			if ($pos !== false)
			{
				$excerpt = substr($excerpt, 0, $pos);
			}
		}

		// If excerpt still too long
		if (strlen($excerpt) > 1024)
		{
			$excerpt = substr($excerpt, 0, 1024);
		}

		return $excerpt;
	}

	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public static function getLanguages()
	{
		if (!isset(self::$languages))
		{
			include(Flattr::getInstance()->getBasePath() . 'languages.php');
			self::$languages = $languages;
		}
		
		return self::$languages;
	}
	
	protected function init()
	{
		if (!$this->settings)
		{
			require_once($this->getBasePath() . 'settings.php');
			$this->settings = new Flattr_Settings();
		}

		if (!$this->postMetaHandler)
		{
			require_once($this->getBasePath() . 'postmeta.php');
			$this->postMetaHandler = new Flattr_PostMeta();
		}
	}

	public function setExcerpt($content)
	{
		global $post;
		return $post->post_content;
	}
	
	public function overrideShareThisFilter($button) {
		$sharethis_buttons = '';
		if ( (is_page() && get_option('st_add_to_page') != 'no') || (!is_page() && get_option('st_add_to_content') != 'no') ) {
			if (!is_feed() && function_exists('st_makeEntries')) {
				$sharethis_buttons = st_makeEntries();
			}
		}
		return $sharethis_buttons . ' <style>.wp-flattr-button iframe{vertical-align:text-bottom}</style>' . $button;
	}

	public function injectIntoTheContent($content)
	{
            global $post;

            if (in_array(get_post_type(), get_option('flattr_post_types'))) {
		$button = $this->getButton();

		$button = '<p class="wp-flattr-button">' . apply_filters('flattr_button', $button) . '</p>';

		if ( get_option('flattr_top', false) ) {
			$result = $button . $content;
		}
		else {
			$result = $content . $button;
		}
		if ( ! post_password_required($post->ID) )
		{
			return $result;
		}
		
            }
            return $content;
	}	
}

Flattr::getInstance();

/**
 * returns the Flattr button
 * Use this from your template
 */
function get_the_flattr_permalink()
{
	return Flattr::getInstance()->getButton(true);
}

/**
 * prints the Flattr button
 * Use this from your template
 */
function the_flattr_permalink()
{
	echo(get_the_flattr_permalink());
}

if (file_exists(WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/flattrwidget.php')) {
    include WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/flattrwidget.php';
}

add_action('admin_init', 'tabber_stylesheet');

/*
 * Enqueue style-file, if it exists.
 */

function tabber_stylesheet() {
    $myStyleUrl = WP_PLUGIN_URL . '/flattr/tabber.css';
    $myStyleFile = WP_PLUGIN_DIR . '/flattr/tabber.css';
    if ( file_exists($myStyleFile) ) {
        wp_register_style('myStyleSheets', $myStyleUrl);
        wp_enqueue_style( 'myStyleSheets');
    }
}

    if(!defined('FLATTRSS_PLUGIN_PATH')) { define(FLATTRSS_PLUGIN_PATH, get_bloginfo('wpurl') . '/wp-content/plugins/flattr'); }
    add_option('flattrss_api_key', "");
    add_option('flattrss_autodonate', false);
    add_option('flattrss_api_secret', "");
    add_option('flattrss_api_oauth_token',"");
    add_option('flattrss_api_oauth_token_secret',"");
    add_option('flattrss_custom_image_url', FLATTRSS_PLUGIN_PATH .'/img/flattr-badge-large.png');
    add_option('flattrss_clicktrack_since_date', date("r"));
    add_option('flattrss_clickthrough_n', 0);
    add_option('flattrss_clicktrack_enabled', true);
    add_option('flattrss_error_reporting', true);
    add_option('flattrss_autosubmit', true);
    add_option('flattr_post_types', array('post','page'));

function flattr_post2rss($content) {
    global $post;

    $flattr = "";
    $flattr_post_types = get_option('flattr_post_types');

    if (is_feed() && in_array(get_post_type(), $flattr_post_types)) {
        $id = $post->ID;
        $md5 = md5($post->post_title);
        $permalink = urlencode(get_permalink( $id ));

        $flattr.= ' <p><a href="'. get_bloginfo('wpurl') .'/?flattrss_redirect&amp;id='.$id.'&amp;md5='.$md5.'" title="Flattr" target="_blank"><img src="'. FLATTRSS_PLUGIN_PATH .'/img/flattr-badge-large.png" alt="flattr this!"/></a></p>';
    }
    return ($content.$flattr);
}

add_filter('the_content_feed', 'flattr_post2rss',999999);

function new_flattrss_autosubmit_action () {

    global $call_n;

    $post = $_POST;

    if (((get_option('flattr_hide') == false) && $post['post_status'] == "publish") && ($post['original_post_status'] != "publish" && (strtotime($post['post_date_gmt']) - strtotime(gmdate("Y-m-d H:i:s")) <= 0)) && ($call_n == 1)) {

        $e = error_reporting();
        error_reporting(E_ERROR);

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

        if (!function_exists('getExcerpt')) {
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
        }

        $content = preg_replace(array('/\<br\s*\/?\>/i',"/\n/","/\r/", "/ +/"), " ", getExcerpt($post));
        $content = strip_tags($content);

        if (strlen(trim($content)) == 0) {
            $content = "(no content provided...)";
        }

        $title = strip_tags($post['post_title']);
        $title = str_replace(array("\"","\'"), "", $title);

        $api_key = get_option('flattrss_api_key');
        $api_secret = get_option('flattrss_api_secret');
        $oauth_token = get_option('flattrss_api_oauth_token');
        $oauth_token_secret = get_option('flattrss_api_oauth_token_secret');

        if (!class_exists('Flattr_Rest')) {
            include 'oAuth/flattr_rest.php';
        }
        $flattr_user = new Flattr_Rest($api_key, $api_secret, $oauth_token, $oauth_token_secret);

        if ($flattr_user->error()) {
            return;
        }

        if(!function_exists("encode")) {
            function encode($string) {
                if (function_exists("mb_detect_encoding")) {
                    $string = (mb_detect_encoding($string, "UTF-8") == "UTF-8" )? $string : utf8_encode($string);
                } else {
                    $string = utf8_encode($string);
                }
                return $string;
            }
        }

        #print_r(array($url, encode($title), $category, encode($content), $tags, $language));

        $flattr_user->submitThing($url, encode($title), $category, encode($content), $tags, $language, get_option('flattr_hide'));

        /*
        if (get_option('flattrss_autodonate') && !isset($_SESSION['flattrss_autodonate_click'])) {
            $flattr_user->clickThing("ead246fc95fc401ce69d15f3981da971");
            $_SESSION['flattrss_autodonate_click'] = true;
        }*/

        error_reporting($e);
    }
    $call_n = 1;
}

if (get_option('flattrss_autosubmit')) {
    add_action('save_post','new_flattrss_autosubmit_action',9999);
}

add_action('init', 'new_flattrss_redirect');
add_action('init', 'new_flattrss_callback');

function new_flattrss_redirect() {
    include_once 'redirect.php';
}

function new_flattrss_callback() {
    include_once 'callback.php';
}

if(is_admin()) {
    $admin_notice = "";

    $oauth_token = get_option('flattrss_api_oauth_token');
    $oauth_token_secret = get_option('flattrss_api_oauth_token_secret');

    $active_plugins = get_option('active_plugins');
    if ( in_array( 'live-blogging/live-blogging.php' , $active_plugins ) && ( get_option('flattr_aut_page', 'off') == 'on' || get_option('flattr_aut', 'off') == 'on' ) ) {
        $admin_notice .= 'echo \'<div id="message" class="updated"><p><strong>Warning:</strong> There is an <a href="http://wordpress.org/support/topic/plugin-live-blogging-how-to-avoid-the_content-of-live_blog_entries" target="_blank">incompatibility</a> with [Liveblog] plugin and automatic Flattr button injection! Automatic injection is disabled as long as [Liveblog] plugin is enabled. You need to use the manual method to add Flattr buttons to your posts.</p></div>\';';
    }

    if (defined('LIBXML_VERSION')) {
        if (version_compare(LIBXML_VERSION, 20627, '<')) {
            $admin_notice .= 'echo \'<div id="message" class="updated"><p><strong>Warning:</strong> There might be an <a href="http://forum.flattr.net/showthread.php?tid=681" target="_blank">incompatibility</a> with your web server running libxml '.LIBXML_VERSION.'. Flattr Plugin requieres at least 20627. You can help improve the Flattr experience for everybody, <a href="mailto:flattr@allesblog.de?subject='.rawurlencode("My webserver is running LIBXML Version ".LIBXML_VERSION).'">please contact me</a> :). See Feedback-tab for details.</p></div>\';';
        }
    } else {
        $admin_notice .= 'echo \'<div id="message" class="error"><p><strong>Error:</strong> Your PHP installation must support <strong>libxml</strong> for Flattr plugin to work!</p></div>\';';
    }

    if (in_array( 'flattrss/flattrss.php' , $active_plugins)) {
        $admin_notice .= 'echo \'<div id="message" class="error"><p><strong>Error:</strong> It is mandatory for <strong>FlattRSS</strong> plugin to be at least deactivated. Functionality and Settings are merged into the Flattr plugin.</p></div>\';';
    }

    if (in_array( 'flattrwidget/flattrwidget.php' , $active_plugins)) {
        $admin_notice .= 'echo \'<div id="message" class="error"><p><strong>Error:</strong> It is mandatory for <strong>Flattr Widget</strong> plugin to be at least deactivated. Functionality and Settings are merged into the Flattr plugin.</p></div>\';';
    }
    
    if ($admin_notice != "") {
        add_action( 'admin_notices',
            create_function('', $admin_notice)
        );
    }

}