<?php
/**
 * @package Flattr
 * @author Michael Henke
 * @version 1.0.1
Plugin Name: Flattr
Plugin URI: http://wordpress.org/extend/plugins/flattr/
Description: Give your readers the opportunity to Flattr your effort
Version: 1.0.1
Author: Michael Henke
Author URI: http://www.codingmerc.com/tags/flattr/
License: This code is (un)licensed under the kopimi (copyme) non-license; http://www.kopimi.com. In other words you are free to copy it, taunt it, share it, fork it or whatever. :)
Comment: The author of this plugin is not affiliated with the flattr company in whatever meaning.
 */

if (session_id() == '') { session_start(); }

class Flattr
{
    /**
     * Javascript API URL without protocol part
     */
    const API_SCRIPT  = 'api.flattr.com/js/0.6/load.js?mode=auto';
    
    const VERSION = "1.0.1";

    /**
     * We should only create Flattr once - make it a singleton
     */
    protected static $instance;

    /**
     * Are we running on default or secure http?
     * @var String http:// or https:// protocol
     */
    var $proto = "http://";
    
    /**
     * construct and initialize Flattr object
     */
    protected function __construct() {
        if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])
            $this->proto = 'https://';
        
        self::default_options();
        
        if (is_admin()) {
            $this->backend();
        } else {
            $this->frontend();
        }
        if (empty($this->postMetaHandler)) {
            require_once($this->getBasePath() . 'postmeta.php');
            $this->postMetaHandler = new Flattr_PostMeta();
        }
    }	
    
    /**
     * prepare Frontend
     */
    protected function frontend() {
        add_action('wp_enqueue_scripts', array($this, 'insert_script'));

        if (get_option('flattr_aut') || get_option('flattr_aut_page')) {
            add_action('the_content', array($this, 'injectIntoTheContent'), 32767);
        }
    }
    
    public function getBasePath()
	{
		if (!isset($this->basePath))
		{
			$this->basePath = WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/';
		}
		
		return $this->basePath;
	}
    
    /**
     * prepare Dashboard
     */
    protected function backend() {
        add_action('admin_init', array($this, 'ajax'));
        add_action('admin_init', array($this, 'insert_script'));
        add_action('admin_init', array($this, 'insert_wizard'));
        add_action('admin_init', array( $this, 'register_settings') );
        add_action('admin_init', array( $this, 'update_user_meta') );
        add_action('admin_menu', array( $this, 'settings') );
        
        if (ini_get('allow_url_fopen') || function_exists('curl_init'))
            add_action('in_plugin_update_message-flattr/flattr.php', 'flattr_in_plugin_update_message');
    }

    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            try
            {
                self::$instance = new self();
            }
            catch(Exception $e)
            {
                Flattr_Logger::log($e->getMessage(), 'Flattr_View::getInstance');
                self::$instance = false;
            }
        }
        return self::$instance;
    }

    public function ajax () {
        
        if (isset ($_GET["q"], $_GET["flattrJAX"])) {
            define('PASS', "passed");
            define('FAIL', "failed");
            define('WARN', "warn");

            $feature = $_GET["q"];

            $retval = array();
            $retval["result"] = FAIL;
            $retval["feature"] = $feature;
            $retval["text"] = $retval["result"];

            switch ($feature) {
                case "cURL" :
                    if (function_exists("curl_init")) {
                        $retval["result"] = PASS;
                        $retval["text"] = "curl_init";
                    }
                    break;
                case "php" :
                        $retval["text"] = PHP_VERSION;
                    if (version_compare(PHP_VERSION, '5.0.0', '>')) {
                        $retval["result"] = WARN;
                    }
                    if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
                        $retval["result"] = PASS;
                    }
                    break;
                case "oAuth" :
                    if (!class_exists('OAuth2Client')) {
                        $retval["result"] = PASS;
                        $retval["text"] = 'OAuth2Client';
                    }
                    break;
                case "Wordpress" :
                    require '../wp-includes/version.php';
                    $retval["text"] = $wp_version;
                    if (version_compare($wp_version, '3.0', '>=')) {
                        $retval["result"] = WARN;
                    }
                    if (version_compare($wp_version, '3.3', '>=')) {
                        $retval["result"] = PASS;
                    }
                    break;
                case "Flattr" :
                    $retval["text"] = "Flattr API v2";
                    
                    $ch = curl_init ('https://api.flattr.com/rest/v2/users/der_michael');
                    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true) ;
                    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false) ;
                    $res = curl_exec ($ch) ;
                    $res = json_decode($res);
                    if (isset($res->type)) {
                        $retval["text"] = "connection established";
                        $retval["result"] = PASS;
                    } else {
                        $retval["text"] = "curl connection error ".curl_error($ch);
                    }
                    curl_close ($ch) ;
                    break;
                default :
                    break;
            }

            print json_encode($retval);
            exit (0);
        } elseif (isset ($_GET["flattrss_api_key"], $_GET["flattrss_api_secret"], $_GET["flattrJAX"])) {
            $retval = array ( "result" => -1,
                              "result_text" => "uninitialised" );
            
            $callback = urlencode(home_url()."/wp-admin/admin.php?page=flattr/flattr.php");
            
            $key = $_GET["flattrss_api_key"];
            $sec = $_GET["flattrss_api_secret"];
            
            update_option('flattrss_api_key', $key);
            update_option('flattrss_api_secret', $sec);
            
            include_once 'flattr_client.php';
            
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
            
            $retval["result_text"] = $client->authorizeUrl();
            $retval["result"] = 0;
            print json_encode($retval);

            exit (0);
        } elseif (isset ($_GET["code"], $_GET["flattrJAX"])) {
            $retval = array ( "result" => -1,
                              "result_text" => "uninitialised" );
            
            $callback = urlencode(home_url()."/wp-admin/admin.php?page=flattr/flattr.php");
            
            $key = get_option('flattrss_api_key');
            $sec = get_option('flattrss_api_secret');
            
            
            include_once 'flattr_client.php';
            
            $access_token = get_option('flattr_access_token', true);
            
            try { 
            
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

                    'access_token'      => $access_token
                )));
                
                $user = $client->getParsed('/user');
                
                $retval["result_text"] = '<img style="float:right;width:48px;height:48px;border:0;" src="'. $user['avatar'] .'"/>'.
                                         '<h3>'.$user['username'].'</h3>'.
                                         '<ul><li>If this is your name and avatar authentication was successfull.</li>'.
                                         '<li>If the name displayed and avatar do not match <a href="/wp-admin/admin.php?page=flattr/flattr.php">start over</a>.</li>'.
                                         '<li>You need to authorize your blog once only!</li></ul>';
                
            } catch (Exception $e) {
                $retval["result_text"] = '<h3>Error</h3><p>'.$e->getMessage().'</p>';
            }
            
            
            $retval["result"] = 0;
            print json_encode($retval);

            exit (0);
        }
    }

        /**
     * initialize default options
     */
    protected static function default_options() {
        add_option('flattr_post_types', array('post','page'));
        add_option('flattr_lng', 'en_GB');
        add_option('flattr_aut', true);
        add_option('flattr_aut_page', true);
        add_option('flattr_atags', 'blog');
        add_option('flattr_cat', 'text');
        add_option('flattr_top', false); 
        add_option('flattr_compact', false); 
        add_option('flattr_button_style', "js");
        add_option('flattrss_custom_image_url', get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png');
        add_option('user_based_flattr_buttons', false);
        add_option('user_based_flattr_buttons_since_time', time());

        add_option('flattrss_button_enabled', true);
    }

    /**
     * Insert Flattr script between <head></head> tags using Wordpress script hooks
     * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script
     */
    public function insert_script() {
        wp_deregister_script( 'flattrscript' );
        wp_register_script( 'flattrscript', $this->proto . self::API_SCRIPT);
        wp_enqueue_script( 'flattrscript' );
    }
    
    public function insert_wizard() {
        wp_deregister_script( 'jquery-dialog' );
        wp_register_script( 'jquery-dialog', get_bloginfo('wpurl') . '/wp-content/plugins/flattr/jquery-ui-1.8.16.dialog.min.js');
        wp_enqueue_script( 'jquery-dialog' );
        wp_deregister_script( 'flattrscriptwizard' );
        wp_register_script( 'flattrscriptwizard', get_bloginfo('wpurl') . '/wp-content/plugins/flattr/wizard.js');
        wp_enqueue_script( 'flattrscriptwizard' );
    }
    
    public function update_user_meta() {
        if (isset($_POST['user_flattr_uid'], $_POST['user_flattr_cat'], $_POST['user_flattr_lng'])) {
            require_once( ABSPATH . WPINC . '/registration.php');
            $user_id = get_current_user_id( );

            update_user_meta( $user_id, "user_flattr_uid", $_POST['user_flattr_uid'] );
            update_user_meta( $user_id, "user_flattr_cat", $_POST['user_flattr_cat'] );
            update_user_meta( $user_id, "user_flattr_lng", $_POST['user_flattr_lng'] );
        }
    }


    public function register_settings() {
        register_setting('flattr-settings-group', 'flattr_post_types');
        register_setting('flattr-settings-group', 'flattr_uid');
        register_setting('flattr-settings-group', 'flattr_lng');
        register_setting('flattr-settings-group', 'flattr_atags');
        register_setting('flattr-settings-group', 'flattr_cat');
        register_setting('flattr-settings-group', 'flattr_compact');
        register_setting('flattr-settings-group', 'flattr_top');
        register_setting('flattr-settings-group', 'flattr_hide');
        register_setting('flattr-settings-group', 'flattr_button_style');
        register_setting('flattr-settings-group', 'flattrss_custom_image_url');
        register_setting('flattr-settings-group', 'user_based_flattr_buttons');
        register_setting('flattr-settings-group', 'flattr_aut_page');
        register_setting('flattr-settings-group', 'flattr_aut');
    }


    public function settings() {
        $menutitle = __('Flattr', 'flattr');

        /**
         * Where to put the flattr settings menu
         */
        if (get_option('user_based_flattr_buttons')) {
            $page = add_submenu_page( "users.php", __('Flattr User Settings'), __('Flattr'), "edit_posts", __FILE__."?user", array($this, 'render_user_settings'));
            add_action( 'admin_print_styles-' . $page, array ($this, 'admin_styles'));
        }
        
        $cap = "manage_options";

        add_menu_page('Flattr',  $menutitle, $cap, __FILE__, '', get_bloginfo('wpurl') . '/wp-content/plugins/flattr'.'/img/flattr-icon_new.png');
        $page = add_submenu_page( __FILE__, __('Flattr'), __('Flattr'), $cap, __FILE__, array($this, 'render_settings'));
        
        /**
         * Using registered $page handle to hook stylesheet loading for admin pages
         * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_style
         */
        add_action( 'admin_print_styles-' . $page, array ($this, 'admin_styles'));
    }
    
    /**
     * Include custom styles for admin pages
     */
    public function admin_styles() {
        wp_register_style( 'flattr_admin_style', plugins_url('flattr.css', __FILE__) );
        wp_enqueue_style( 'flattr_admin_style' );
        wp_register_style( 'jquery-dialog_style', plugins_url('jquery-ui-1.8.16.dialog.css', __FILE__) );
        wp_enqueue_style( 'jquery-dialog_style' );
    }

    public function render_user_settings() {
        include('settings-templates/header.php');
        include('settings-templates/user.php');
        include('settings-templates/common.php');
        include('settings-templates/footer.php');
    }

    public function render_settings() {
        include('settings-templates/header.php');
        include('settings-templates/plugin.php');
        include('settings-templates/common.php');
        include('settings-templates/footer.php');
    }

    /**
     * Insert the flattr button into the post content
     * @global type $post
     * @param type $content
     * @return string 
     */
    public function injectIntoTheContent($content) {
        global $post;

        if ( post_password_required($post->ID) ) {
            return $content;
        }

        if ( ( is_page($post) && !get_option('flattr_aut_page') ) || !get_option('flattr_aut') ) {
            return $content;
        }

        if (in_array(get_post_type(), (array)get_option('flattr_post_types', array())) && !is_feed()) {
            $button = $this->getButton();
            $button = '<p class="wp-flattr-button">'.$button.'</p>';

            if ( get_option('flattr_top', false) ) {
                    $content = $button . $content;
            }
            else {
                    $content = $content . $button;
            }
        }
        return $content;
    }	
   
    /**
     * https://flattr.com/submit/auto?user_id=USERNAME&url=URL&title=TITLE&description=DESCRIPTION&language=LANGUAGE&tags=TAGS&hidden=HIDDEN&category=CATEGORY
     * @see http://blog.flattr.net/2011/11/url-auto-submit-documentation/
     */
    public function getButton($type = null, $post = null) {
        if (!$post)
        {
            $post = $GLOBALS['post'];
        }

        if (get_post_meta($post->ID, '_flattr_btn_disabled', true))
        {
                return '';
        }
        if (get_option('user_based_flattr_buttons_since_time')< strtotime(get_the_time("c",$post)))
            $flattr_uid = (get_option('user_based_flattr_buttons')&& get_user_meta(get_the_author_meta('ID'), "user_flattr_uid", true)!="")? get_user_meta(get_the_author_meta('ID'), "user_flattr_uid", true): get_option('flattr_uid');
        else
            $flattr_uid = get_option('flattr_uid');
        if (!$flattr_uid) {
                return '';
        }
        
        $selectedLanguage = get_post_meta($post->ID, '_flattr_post_language', true);
        if (empty($selectedLanguage)) {
                $selectedLanguage = (get_user_meta(get_the_author_meta('ID'), "user_flattr_lng", true)!="")? get_user_meta(get_the_author_meta('ID'), "user_flattr_lng", true): get_option('flattr_lng');
        }

        $additionalTags = get_option('flattr_atags', 'blog');

        $selectedCategory = get_post_meta($post->ID, '_flattr_post_category', true);
        if (empty($selectedCategory)) {
                $selectedCategory = (get_option('user_based_flattr_buttons')&& get_user_meta(get_the_author_meta('ID'), "user_flattr_cat", true)!="")? get_user_meta(get_the_author_meta('ID'), "user_flattr_cat", true): get_option('flattr_cat');
        }
       
        $hidden = get_post_meta($post->ID, '_flattr_post_hidden', true);
        if ($hidden == '') {
                $hidden = get_option('flattr_hide', false);
        }

        $buttonData = array(

                'user_id'	=> $flattr_uid,
                'url'		=> get_permalink(),
                'compact'	=> (get_option('flattr_compact', false) ? true : false ),
                'hidden'	=> $hidden,
                'language'	=> $selectedLanguage,
                'category'	=> $selectedCategory,
                'title'		=> strip_tags(get_the_title()),
                'description'		=> strip_tags(preg_replace('/\<br\s*\/?\>/i', "\n", $post->post_content)),
                'tags'		=> trim(strip_tags(get_the_tag_list('', ',', '')) . ',' . $additionalTags, ', ')

        );

        if (empty($buttonData['description']) && !in_array($buttonData['category'], array('images', 'video', 'audio')))
        {
                $buttonData['description'] = get_bloginfo('description');

                if (empty($buttonData['description']) || strlen($buttonData['description']) < 5)
                {
                        $buttonData['description'] = $buttonData['title'];
                }
        }


        if (isset($buttonData['user_id'], $buttonData['url'], $buttonData['language'], $buttonData['category']))
        {
                switch (empty($type) ? get_option('flattr_button_style') : $type) {
                    case "text":
                        $retval = '<a href="'. static_flattr_url($post).'" title="Flattr" target="_blank">Flattr this!</a>';
                        break;
                    case "image":
                        $retval = '<a href="'. static_flattr_url($post).'" title="Flattr" target="_blank"><img src="'. get_bloginfo('wpurl') . '/wp-content/plugins/flattr/img/flattr-badge-large.png" alt="flattr this!"/></a>';
                        break;
                    case "autosubmitUrl":
                        $retval = $this->getAutosubmitUrl($buttonData);
                        break;
                    default:
                        $retval = $this->getButtonCode($buttonData);
                }
                return $retval;
        }
        return '';
    }

    protected function getButtonCode($params)
    {
            $rev = sprintf('flattr;uid:%s;language:%s;category:%s;',
                    $params['user_id'],
                    $params['language'],
                    $params['category']
            );

            if (!empty($params['tags']))
            {
                    $rev .= 'tags:'. htmlspecialchars($params['tags']) .';';
            }

            if ($params['hidden'])
            {
                    $rev .= 'hidden:1;';
            }

            if ($params['compact'])
            {
                    $rev .= 'button:compact;';
            }

            return sprintf('<a class="FlattrButton" style="display:none;" href="%s" title="%s" rev="%s">%s</a>',
                    $params['url'],
                    htmlspecialchars($params['title']),
                    $rev,
                    htmlspecialchars($params['description'])
            );
    }

    function getAutosubmitUrl($params) {
        if (isset($params['compact']))
        {
            unset($params['compact']);
        }
        $params = array_filter($params);
        return 'https://flattr.com/submit/auto?' . http_build_query($params);
    }

    protected static $languages;
    public static function getLanguages() {
        if (empty(self::$languages)) {
            self::$languages['sq_AL'] = 'Albanian';
            self::$languages['ar_DZ'] = 'Arabic';
            self::$languages['be_BY'] = 'Belarusian';
            self::$languages['bg_BG'] = 'Bulgarian';
            self::$languages['ca_ES'] = 'Catalan';
            self::$languages['zh_CN'] = 'Chinese';
            self::$languages['hr_HR'] = 'Croatian';
            self::$languages['cs_CZ'] = 'Czech';
            self::$languages['da_DK'] = 'Danish';
            self::$languages['nl_NL'] = 'Dutch';
            self::$languages['en_GB'] = 'English';
            self::$languages['et_EE'] = 'Estonian';
            self::$languages['fi_FI'] = 'Finnish';
            self::$languages['fr_FR'] = 'French';
            self::$languages['de_DE'] = 'German';
            self::$languages['el_GR'] = 'Greek';
            self::$languages['iw_IL'] = 'Hebrew';
            self::$languages['hi_IN'] = 'Hindi';
            self::$languages['hu_HU'] = 'Hungarian';
            self::$languages['is_IS'] = 'Icelandic';
            self::$languages['in_ID'] = 'Indonesian';
            self::$languages['ga_IE'] = 'Irish';
            self::$languages['it_IT'] = 'Italian';
            self::$languages['ja_JP'] = 'Japanese';
            self::$languages['ko_KR'] = 'Korean';
            self::$languages['lv_LV'] = 'Latvian';
            self::$languages['lt_LT'] = 'Lithuanian';
            self::$languages['mk_MK'] = 'Macedonian';
            self::$languages['ms_MY'] = 'Malay';
            self::$languages['mt_MT'] = 'Maltese';
            self::$languages['no_NO'] = 'Norwegian';
            self::$languages['pl_PL'] = 'Polish';
            self::$languages['pt_PT'] = 'Portuguese';
            self::$languages['ro_RO'] = 'Romanian';
            self::$languages['ru_RU'] = 'Russian';
            self::$languages['sr_RS'] = 'Serbian';
            self::$languages['sk_SK'] = 'Slovak';
            self::$languages['sl_SI'] = 'Slovenian';
            self::$languages['es_ES'] = 'Spanish';
            self::$languages['sv_SE'] = 'Swedish';
            self::$languages['th_TH'] = 'Thai';
            self::$languages['tr_TR'] = 'Turkish';
            self::$languages['uk_UA'] = 'Ukrainian';
            self::$languages['vi_VN'] = 'Vietnamese';
        }

        return self::$languages;
    }
    
    protected static $categories;
    public static function getCategories() {
        if (empty(self::$categories)) {
            self::$categories = array('text', 'images', 'audio', 'video', 'software', 'rest');
        }
        return self::$categories;
    }
    
    public function flattr_in_plugin_update_message() {

        $url = 'http://plugins.trac.wordpress.org/browser/flattr/trunk/readme.txt?format=txt';
        $data = "";

        if ( ini_get('allow_url_fopen') )
            $data = file_get_contents($url);
        else
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
                $data = curl_exec($ch);
                curl_close($ch);
            }


        if ($data) {
            $matches = null;
            $regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*' . preg_quote(Flattr::VERSION) . '\s*=|$)~Uis';

            if (preg_match($regexp, $data, $matches)) {
                $changelog = (array) preg_split('~[\r\n]+~', trim($matches[1]));

                echo '</div><div class="update-message" style="font-weight: normal;"><strong>What\'s new:</strong>';
                $ul = false;
                $version = 99;

                foreach ($changelog as $index => $line) {
                    if (version_compare($version, Flattr::VERSION,">"))
                    if (preg_match('~^\s*\*\s*~', $line)) {
                        if (!$ul) {
                            echo '<ul style="list-style: disc; margin-left: 20px;">';
                            $ul = true;
                        }
                        $line = preg_replace('~^\s*\*\s*~', '', htmlspecialchars($line));
                        echo '<li style="width: 50%; margin: 0;">' . $line . '</li>';
                    } else {
                        if ($ul) {
                            echo '</ul>';
                            $ul = false;
                        }

                        $version = trim($line, " =");
                        echo '<p style="margin: 5px 0;">' . htmlspecialchars($line) . '</p>';
                    }
                }

                if ($ul) {
                    echo '</ul><div style="clear: left;"></div>';
                }

                echo '</div>';
            }
        }
    }
}

function static_flattr_url($post) {
    $id = $post->ID;
    $md5 = md5($post->post_title);

    return (get_bloginfo('wpurl') .'/?flattrss_redirect&amp;id='.$id.'&amp;md5='.$md5);
}

function flattr_post2rss($content) {
    global $post;

    $flattr = "";
    $flattr_post_types = (array)get_option('flattr_post_types', array());
    
    $meta = get_post_meta($post->ID, '_flattr_btn_disable');
    
    $postmeta = isset($meta['_flattr_btn_disable'])? $meta['_flattr_btn_disable'] : true;
   
    if (($postmeta) && is_feed() && in_array(get_post_type(), $flattr_post_types)) {
        $flattr.= ' <p><a href="'. static_flattr_url($post).'" title="Flattr" target="_blank"><img src="'. get_option('flattrss_custom_image_url') .'" alt="flattr this!"/></a></p>';
    }
    return ($content.$flattr);
}

add_action('init', 'new_flattrss_redirect');
add_action('init', 'flattr_init');

function flattr_init() {
    include_once 'init.php';
}

function new_flattrss_redirect() {
    include_once 'redirect.php';
}

if(get_option('flattrss_button_enabled')) {
    add_filter('the_content_feed', 'flattr_post2rss',999999);
    
    add_action('atom_entry', 'flattr_feed_atom_item');
    add_action('rss2_item', 'flattr_feed_rss2_item');

    add_action('rss2_ns', 'rss_ns');
}

function flattr_feed_atom_item() {
    global $post;
    echo '		<link rel="payment" href="' . htmlspecialchars(Flattr::getInstance()->getButton("autosubmitUrl", $post)) . '" type="text/html" />'."\n";
}

function flattr_feed_rss2_item() {
    global $post;
    echo '	<atom:link rel="payment" href="' . htmlspecialchars(Flattr::getInstance()->getButton("autosubmitUrl", $post)) . '" type="text/html" />'."\n";
}

function rss_ns() {
    //echo 'xmlns:atom="http://www.w3.org/2005/Atom"'; 
}


$call_n = 0; # Do not delete! It will break autosubmit.
function new_flattrss_autosubmit_action () {

    global $call_n;

    $call_n += 1;
    $post = $_POST;

    if (($post['post_status'] == "publish") && (get_post_meta($post['ID'], "flattrss_autosubmited", true)=="") && ($call_n == 2) && (get_the_time('U') <= time())) {

        $url = get_permalink($post['ID']);
        $tagsA = get_the_tags($post['ID']);
        $tags = "";

        if (!empty($tagsA)) {
            foreach ($tagsA as $tag) {
                if (strlen($tags)!=0){
                    $tags .=",";
                }
                $tags .= $tag->name;
            }
        }

        $additionalTags = get_option('flattr_atags', 'blog');
        if (!empty($additionalTags)) {
            $tags .= ',' . $additionalTags;
        }
        $tags = trim($tags, ', ');

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

        $oauth_token = get_option('flattrss_api_key');
        $oauth_token_secret = get_option('flattrss_api_secret');
        
        $flattr_access_token = get_option('flattr_access_token');
        
        if (get_option('user_based_flattr_buttons')< strtotime(get_the_time("c",$post))) {
            $user_id = get_current_user_id();
            $flattr_access_token = (get_user_meta( $user_id, "user_flattrss_api_oauth_token",true)!="")?get_user_meta( $user_id, "user_flattrss_api_oauth_token",true):get_option('flattr_access_token');
         
        }
        
        include_once 'flattr_client.php';
        
        $client = new OAuth2Client( array_merge(array(
            'client_id'         => $oauth_token,
            'client_secret'     => $oauth_token_secret,
            'base_url'          => 'https://api.flattr.com/rest/v2',
            'site_url'          => 'https://flattr.com',
            'authorize_url'     => 'https://flattr.com/oauth/authorize',
            'access_token_url'  => 'https://flattr.com/oauth/token',

            'redirect_uri'      => urlencode(home_url()."/wp-admin/admin.php?page=flattr/flattr.php"),
            'scopes'            => 'thing+flattr',
            'token_param_name'  => 'Bearer',
            'response_type'     => 'code',
            'grant_type'        => 'authorization_code',
            'refresh_token'     => null,
            'code'              => null,
            'developer_mode'    => false,

            'access_token'      => $flattr_access_token,
        )));

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

        $server = $_SERVER["SERVER_NAME"];
        $server = preg_split("/:/", $server);
        $server = $server[0];

        $hidden = (get_option('flattr_hide', true) || get_post_meta($post->ID, '_flattr_post_hidden', true) ||$server == "localhost")? true:false;
        
        try {
            $response = $client->post('/things', array (
                    "url" => $url, 
                    "title" => encode($title), 
                    "category" => $category, 
                    "description" => encode($content), 
                    "tags"=> $tags, 
                    "language" => $language, 
                    "hidden" => $hidden)
                );

            if (strpos($response->responseCode,'20') === 0)
                add_post_meta($post['ID'], "flattrss_autosubmited", "true");

        } catch (Exception $e) {
            
        }
    }
}

if (get_option('flattrss_autosubmit') && get_option('flattr_access_token')) {
    add_action('save_post','new_flattrss_autosubmit_action',9999);
}

/**
 * prints the Flattr button
 * Use this from your template
 */
function the_flattr_permalink()
{
    echo Flattr::getInstance()->getButton();
}

// Make sure that the flattr object is ran at least once
$flattr = Flattr::getInstance();
