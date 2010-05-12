<?php
/*
Plugin Name: Flattr
Plugin URI: http://flattr.com/
Description: Give your readers the opportunity to Flattr your effort
Version: 0.8
Author: Flattr.com
Author URI: http://flattr.com/
*/

class Flattr
{
	const WP_VERSION = '0.8';
	const WP_SCRIPT = 'http://api.flattr.com/button/load.js';

	/** @var array */
	protected static $categories = array('text', 'images', 'audio', 'video', 'software', 'rest');
	/** @var array */
	protected static $languages;
	/** @var Flattr */
	protected static $instance;

	/** @var Flattr_Settings */
	protected $settings;

	public function __construct()
	{
		if (is_admin())
		{
			$this->init();
		}
		
		if (get_option('flattr_aut', 'off') == 'on')
		{
			remove_filter('get_the_excerpt', 'wp_trim_excerpt');
			add_filter('get_the_excerpt', array($this, 'filterFulHack1'), 9);
			add_filter('get_the_excerpt', array($this, 'filterFulHack2'), 11);
			add_filter('the_content', array($this, 'injectIntoTheContent')); 
		}
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
	
	public function getButton()
	{
		global $post;
		
		if (get_post_meta($post->ID, '_flattr_btn_disabled', true))
		{
			return '';
		}
		
		$uid = get_option('flattr_uid');
		
		$selectedLanguage = get_post_meta($post->ID, '_flattr_post_language', true);
		$selectedCategory = get_post_meta($post->ID, '_flattr_post_category', true);
	
		if (!$selectedLanguage)
		{
			$selectedLanguage = get_option('flattr_lng');
		}
		
		if (!$selectedCategory)
		{
			$selectedCategory = get_option('flattr_cat');
		}
	
		if (strlen($uid) && strlen($selectedCategory) && strlen($selectedLanguage))
		{
			return $this->getButtonCode($uid, $selectedCategory, get_the_title(), $this->getExcerpt(), strip_tags(get_the_tag_list('', ',', '')), get_permalink(), $selectedLanguage );
		}
	}
	
	protected function getButtonCode($userID, $category, $title, $description, $tags, $url, $language)
	{
		$cleaner = create_function('$expression', "return trim(preg_replace('~\r\n|\r|\n~', ' ', addslashes(\$expression)));");

		$output = "<script type=\"text/javascript\">\n";
		$output .= "var flattr_wp_ver = '" . Flattr::WP_VERSION  . "';\n";
		$output .= "var flattr_uid = '" . $cleaner($userID)      . "';\n";
		$output .= "var flattr_url = '" . $cleaner($url)         . "';\n";
		$output .= "var flattr_lng = '" . $cleaner($language)    . "';\n";
		$output .= "var flattr_cat = '" . $cleaner($category)    . "';\n";
		if($tags) { $output .= "var flattr_tag = '". $cleaner($tags) ."';\n"; }
		if (get_option('flattr_compact', false)) { $output .= "var flattr_btn = 'compact';\n"; }
		$output .= "var flattr_tle = '". $cleaner($title) ."';\n";
		$output .= "var flattr_dsc = '". $cleaner($description) ."';\n";
		$output .= "</script>\n";
		$output .= '<script src="' . Flattr::WP_SCRIPT . '" type="text/javascript"></script>';
		
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
		if ( strlen($excerpt) > $excerpt_max_length )
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
			include('languages.php');
			self::$languages = $languages;
		}
		
		return self::$languages;
	}
	
	protected function init()
	{
		if (!$this->settings)
		{
			require_once('settings.php');
			$this->settings = new Flattr_Settings();
		}
		
		if (!$this->postMetaHandler)
		{
			require_once('postmeta.php');
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
 * Use this from template
 */
function the_flattr_permalink()
{
	echo(get_the_flattr_permalink());
}