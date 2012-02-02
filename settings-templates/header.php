<?php

/**
 * Description:
 * 
 * Description goes here...
 * 
 * @author Michael Henke (aphex3k@gmail.com)
 */
?>
<div class="wrap flattr-wrap">
    <div>
        <div style="float:right;margin-left: 10px;"><img src="../wp-content/plugins/flattr/img/flattr-logo-beta-small.png" alt="Flattr Beta Logo"/><br />
            <ul style="margin-top: 10px;">
                <li style="display: inline;">
                    <script type="text/javascript">
                        var flattr_uid = "der_michael";
                        var flattr_btn = "compact";
                        var flattr_tle = "Wordpress Flattr plugin";
                        var flattr_dsc = "Give your readers the opportunity to Flattr your effort. See http://wordpress.org/extend/plugins/flattr/ for details.";
                        var flattr_cat = "software";
                        var flattr_tag = "wordpress,plugin,flattr,rss";
                        var flattr_url = "http://wordpress.org/extend/plugins/flattr/";
                    </script><script src="<?php echo (isset($_SERVER['HTTPS'])) ? 'https' : 'http'; ?>://api.flattr.com/button/load.js" type="text/javascript"></script>
                </li>
                <li style="display: inline-block;position:relative; top: -6px;"><a href="https://flattr.com/donation/give/to/der_michael" style="color:#ffffff;text-decoration:none;background-image: url('<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/flattr/img/bg-boxlinks-green.png');border-radius:3px;text-shadow:#666666 0 1px 1px;width:53px;padding:1px;padding-top: 2px;padding-bottom: 2px;display:block;text-align:center;font-weight: bold;" target="_blank">Donate</a></li>
            </ul>
        </div>
        <div id="icon-options-general" class="icon32 flattr"><br></div>
        <h2><?php _e('Flattr Settings'); ?></h2>