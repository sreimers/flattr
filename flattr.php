<?php
/*
Plugin Name: Flattr
Plugin URI: http://flattr.com/
Description: Give your readers the opportunity to Flattr your effort
Version: 0.9.4
Author: Flattr.com
Author URI: http://flattr.com/
*/

class Flattr
{
	const VERSION = '0.9.4';
	const WP_MIN_VER = '2.9';
	const PHP_MIN_VER = '5.0.0';
	const API_SCRIPT  = 'http://api.flattr.com/button/load.js?v=0.2';

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
		if ( get_option('flattr_aut', 'off') == 'on' || get_option('flattr_aut_page', 'off') == 'on' )
		{
			remove_filter('get_the_excerpt', 'wp_trim_excerpt');
			add_filter('get_the_excerpt', array($this, 'filterFulHack1'), 9);
			add_filter('get_the_excerpt', array($this, 'filterFulHack2'), 11);
			add_filter('the_content', array($this, 'injectIntoTheContent')); 
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
		
		if (version_compare(PHP_VERSION, self::PHP_MIN_VER, '<'))
		{
			$this->addAdminNoticeMessage('<strong>Warning:</strong> The Flattr plugin requires PHP5. You are currently using '. PHP_VERSION);
  			add_action( 'admin_notices', array(&$this, 'adminNotice') );
			return false;
		}
		
		if (version_compare($wp_version, self::WP_MIN_VER, '<'))
		{
			$this->addAdminNoticeMessage('<strong>Warning:</strong> The Flattr plugin requires WordPress '. self::WP_MIN_VER .' or later. You are currently using '. $wp_version);
			return false;
		}
		
		return true;
	}

	public function filterFulHack1($content)
	{
		remove_filter("the_content", array($this, 'injectIntoTheContent'));
		return $content;
	}
	
	public function filterFulHack2($content)
	{
		add_filter("the_content", array($this, 'injectIntoTheContent'));
		return $content;
	}
	
	public function getBasePath()
	{
		if (!isset($this->basePath))
		{
			$this->basePath = WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/';
		}
		
		return $this->basePath;
	}

	public function getButton()
	{
		global $post;

		if ( ($post->post_type == 'page' && get_option('flattr_aut_page', 'off') != 'on') || ($post->post_type != 'page' && get_option('flattr_aut', 'off') != 'on') )
		{
			return '';
		}

		if (get_post_meta($post->ID, '_flattr_btn_disabled', true))
		{
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

			'user_id'	=> get_option('flattr_uid'),
			'url'		=> get_permalink(),
			'compact'	=> ( get_option('flattr_compact', false) ? true : false ),
			'hide'		=> $hidden,
			'language'	=> $selectedLanguage,
			'category'	=> $selectedCategory,
			'title'		=> get_the_title(),
			'body'		=> $this->getExcerpt(),
			'tag'		=> strip_tags(get_the_tag_list('', ',', ''))

		);

		if (isset($buttonData['user_id'], $buttonData['url'], $buttonData['language'], $buttonData['category']))
		{
			return $this->getButtonCode($buttonData);
		}
	}

	protected function getButtonCode($params)
	{
		$cleaner = create_function('$expression', "return trim(preg_replace('~\r\n|\r|\n~', ' ', addslashes(\$expression)));");

		$output = "<script type=\"text/javascript\">\n";
		$output .= "var flattr_wp_ver = '" . self::VERSION  . "';\n";
		$output .= "var flattr_uid = '" . $cleaner($params['user_id'])      . "';\n";
		$output .= "var flattr_url = '" . $cleaner($params['url'])         . "';\n";

		if ($params['compact'])
		{
			$output .= "var flattr_btn = 'compact';\n";
		}
		
		if ($params['hide'])
		{
			$output .= "var flattr_hide = 1;\n";
		}
		else
		{
			$output .= "var flattr_hide = 0;\n";
		}

		$output .= "var flattr_lng = '" . $cleaner($params['language'])    . "';\n";
		$output .= "var flattr_cat = '" . $cleaner($params['category'])    . "';\n";

		$output .= "var flattr_tle = '". $cleaner($params['title']) ."';\n";
		$output .= "var flattr_dsc = '". $cleaner($params['body']) ."';\n";

		if ($tags)
		{
			$output .= "var flattr_tag = '". $cleaner($params['tags']) ."';\n";
		}

		$output .= "</script>\n";
		$output .= '<script src="' . self::API_SCRIPT . '" type="text/javascript"></script>';
		
		return $output;
	}

	public static function getCategories()
	{
		return self::$categories;
	}

	protected function getExcerpt($excerpt_max_length = 1024)
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
		$excerpt = preg_replace('/httpvh:\/\/[^ ]+/', '', $excerpt); // hack for smartyoutube plugin
	
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
		if (strlen($excerpt) > $excerpt_max_length)
		{
			$excerpt = substr($excerpt, 0, $excerpt_max_length);
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
	
	public function injectIntoTheContent($content)
	{
		return $content . $this->getButton();
	}	
}

Flattr::getInstance();

/**
 * returns the Flattr button
 * Use this from your template
 */
function get_the_flattr_permalink()
{
	return Flattr::getInstance()->getButton();
}

/**
 * prints the Flattr button
 * Use this from your template
 */
function the_flattr_permalink()
{
	echo(get_the_flattr_permalink());
}
