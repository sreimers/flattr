=== Flattr ===
Contributors: flattr.com
Tags: flattr, donate, micropayments
Requires at least: 2.9.0
Tested up to: 2.9.2
Stable tag: trunk

This plugin allows you to easily add a Flattr button to your wordpress blog.

== Description ==

Flattr was founded to help people share money, not only content. Before Flattr, the only reasonable way to donate has been to use Paypal or other systems to send money to people. The threshold for this is quite high. People would just ignore sending donations if it wasn't for a really important cause. Sending just a small sum has always been a pain in the ass. Who would ever even login to a payment system just to donate €0.01? And €10 was just too high for just one blog entry we liked...

Flattr solves this issue. When you're registered to flattr, you pay a small monthly fee. You set the amount yourself. In the end of the month, that fee is divided between all the things you flattered. You're always logged in to the account. That means that giving someone some flattr-love is just a button away. And you should! Clicking one more button doesn't add to your fee. It just divides the fee between more people! Flattr tries to encourage people to share. Not only pieces of content, but also some money to support the people who created them. With love! 

**Flattr requires an account at flattr.com!**

== Installation ==

1. Upload the folder 'flattr' to your server in the folder '/wp-content/plugins/'
2. Go to the WordPress control panel and find the 'Plugins' section
3. Activate the plugin 'Flattr'
4. Go to the 'Options' section and select 'Flattr'
5. Select your default category (which usually would be 'text' if you have a normal blog), select your default language and type in your Flattr user ID (your user ID can be found on your dashboard on http://flattr.com/)
6. If you want the Flattr button to be automagically included at the end of your posts, leave the checkbox checked
7. If you want to add the Flattr button manually in your theme, uncheck the checkbox and use the following code snippet:
8. When writing or editing a blog post you have the ability to select category and language for this specific blog post.

`<?php the_flattr_permalink(); ?>`

8. Live long and prosper. :)


== Changelog ==

= 0.7 =
* Changed category setting to select box instead of input field.
* Added setting for default language.
* Added ability to edit category and language settings per post.

= 0.6 =
* httpvh (Smart YouTube) urls are now stripped when creating excerpt from post content.

= 0.5 =
* Fixed a bug that caused blog posts to display incorrectly when no excerpt was entered. 

= 0.4 =
* First public version

== Upgrade Notice ==

= 0.7 =
Language support. Upgrade required.

= 0.6 =
Previous version didnt work with Smart YouTube when no excerpt was entered.

= 0.5 =
Previous version makes your blog posts display incorrectly if you don't enter an excerpt.

= 0.4 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.


== Support ==

You can always ask us for help on flattrbeta@flattr.com - or twitter to #flattr and ask the whole community!
