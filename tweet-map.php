<?php
/*
Plugin Name: Tweet Map
Plugin URI: http://worldtravelblog.com/code/tweet-map-plugin
Description: This plugin displays your geo tagged Twitter tweets on google maps including showing your Twitter, Twitpic, Twitgoo, and yfrog photos. 
Version: 0.9.4
Author: Peter Rosanelli
Author URI: http://www.worldtravelblog.com
*/

/**
* LICENSE
* This file is part of Tweet Map Plugin.
*
* Tweet Map Plugin is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*
* @package    tweet-map
* @author     Peter Rosanelli <peter@worldtravelblog.com>
* @copyright  Copyright 2011 Peter Rosanelli
* @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
* @version    0.9.4
* @link       http://worldtravelblog.com/code/tweet-map-plugin
*/

global $wpdb;

require_once('settings.php');
require_once('generate-map.php');

register_activation_hook( __FILE__,  array('TweetMap', 'install' ));
register_activation_hook(__FILE__, array('TweetMap', 'scheduleCron' ));
register_deactivation_hook(__FILE__, array('TweetMap', 'unscheduleCron' ));	
register_deactivation_hook(__FILE__, array('TweetMap', 'dropTweetMapTable' ));
	
add_action('admin_menu',  array('TweetMap','adminMenuHook'));
add_action('wp_enqueue_scripts',  array('TweetMap','wpEnqueueScripts'));
add_action('hourly_tweetmap_sync', array('TweetMap', 'syncTweets'));
add_action('wp_head', array('TweetMap', 'wpHead'));
add_action('wp_footer', array('TweetMap', 'wpFooter'));
add_shortcode('tweet-map', array('TweetMap', 'shortcodeHandler'));

class TweetMap {
	
	const DEFAULT_MAX_TWEETS = 20;
	const MAX_TWEETS = 100;
	const TWITTER_URL = 'https://api.twitter.com/1/statuses/user_timeline.json?include_entities=1&screen_name=';
	const TWEET_MAP_TABLE = 'tweetmap';
	
	static $add_script;
	
	// for counting the number of map instances on a single page
	static $instanceCount = 0;
	
	/**
	*  add tweet map link to the admin menu
	*/
	function adminMenuHook() {
		add_options_page('Tweet Map Settings', 'Tweet Map', 'manage_options', 'tweetmap-menu', array('TweetMap', 'adminMenuOptions' ) );	
	}
	
	/**
	* displays tweet map admin page, updates options, and syncs tweets
	*/
	function adminMenuOptions() {
		
		echo( '<h2>Tweet Map Settings</h2>' );
		
		wp_nonce_field( 'update-options' );

		if(isset($_POST['submit']) && ( $_POST['submit'] == 'Update Options' || $_POST['submit'] == 'Sync Tweets' ) ) {

			// Update Tweet Map Settings
			update_option('tweetmap_twitter_username', $_POST['twitter_username']);
			
			update_option('tweetmap_custom_css', $_POST['custom_css']);
				
			// check if max tweets parameter is a number and gt 0 and lt MAX_TWEETS
			// if not then set it to the default number of tweets
			update_option( 'tweetmap_max_tweets', self::DEFAULT_MAX_TWEETS);
			if( is_numeric($_POST['max_tweets']) ) {
				$max_tweets = (int) $_POST['max_tweets'];
				if($max_tweets >= 0 && $max_tweets < self::MAX_TWEETS) {
					update_option( 'tweetmap_max_tweets', $max_tweets);
				}
			}
			
			update_option('tweetmap_google_api_key', $_POST['google_api_key']);				
				
			if(isset($_POST['google_api_ssl']) && $_POST['google_api_ssl'] == 'true') {
				update_option('tweetmap_google_api_ssl', $_POST['google_api_ssl']);	
			} else {
				update_option('tweetmap_google_api_ssl', '');
			}
			
			if(isset($_POST['use_google_api_key']) && $_POST['use_google_api_key'] == 'true') {
				update_option('tweetmap_use_google_api_key', $_POST['use_google_api_key']);	
			} else {
				update_option('tweetmap_use_google_api_key', '');
			}
					
			echo( '<div class="updated"><p>Settings Updated</p></div>' );
			
		}
		
		if(isset($_POST['submit']) && $_POST['submit'] == 'Sync Tweets' ) {
			self::syncTweets();
			
			echo( '<div class="updated"><p>Tweets Synced</p></div>' );
		}
		
		tweetmap_settings();
	}
	
	/**
	* syncs tweets with database using Twitter's Rest Service.
	*/
	function syncTweets() {
		global $wpdb;
		
		list( $twitterTweetIds, $twitterTweetsById ) = self::getTweets();

		if(count($twitterTweetsById) < get_option('tweetmap_max_tweets')) {
			// get page 2 of tweets to try and reach max tweets
			list( $twitterTweetIds, $twitterTweetsById ) =  self::getTweets($twitterTweetIds, $twitterTweetsById, 2);	
		}
		
		// put the database tweets ("old tweets") into an array for comparison to the twitter tweets
		$wpTweets = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix.self::TWEET_MAP_TABLE);
		$wpTweetIds = array();
		foreach($wpTweets as $tweet) {
			$wpTweetIds[] = $tweet->tweet_id;
		}
		
		// find new tweets from twitter
		$newTweets = array_diff($twitterTweetIds, $wpTweetIds);
	
		// find old tweets in the database
		$oldTweets = array_diff($wpTweetIds, $twitterTweetIds);
		
		// insert new tweets into database
		foreach($newTweets as $tweetId) {
			
			$tweet = $twitterTweetsById[$tweetId];
			
			$wpdb->insert( $wpdb->prefix.self::TWEET_MAP_TABLE, array(
				'tweet_id' => $tweet->id_str, 
				'text' => $tweet->text,
				'html_text' => self::convertTweetToHtml($tweet),
				'latitude' => $tweet->geo->coordinates[0],
				'longitude' => $tweet->geo->coordinates[1],
				'created_at' => $tweet->created_at
			), array( '%s', '%s', '%s', '%s', '%s', '%s' ) );			
			
		}
		
		// remove old tweets from database
		if(count($oldTweets) > 0) {
			$wpdb->query('DELETE FROM '.$wpdb->prefix.self::TWEET_MAP_TABLE.' WHERE tweet_id in ('.join(',', $oldTweets). ');');
		}
	}
	
	/**
	*
	* Makes Tweet Rest call to get tweets and stores them in an 2 arrays 
	* 
	* @param array of tweet ids
	* @param array of tweets by id
	* @param results page
	* @return array of tweet ids and array of tweets by id
	*/
	function getTweets($twitterTweetIds = array(), $twitterTweetsById = array(), $page = 1) {
		if(get_option('tweetmap_twitter_username') != "") {
			$url = self::TWITTER_URL.get_option('tweetmap_twitter_username')."&count=".get_option('tweetmap_max_tweets')*$page."&page=".$page;
			$response = file_get_contents($url);
			$tweets = json_decode($response);
					
			// put the twitter tweets (new tweets) in an array for comparison to the database tweets
			// use the tweet id as a string b/c the php json_decode function converts long ints into floats
			if(isset($tweets) && is_array($tweets)) {
				foreach($tweets as $tweet) {			
					// stop if max tweets is meet
					if(count($twitterTweetIds) >= get_option('tweetmap_max_tweets')) {
						break;
					}
					
					// get only geo tagged tweets
					if($tweet->geo && $tweet->geo->coordinates) {
						$twitterTweetIds[] = $tweet->id_str;
						$twitterTweetsById[$tweet->id_str] = $tweet;
					}
		 		}
			}
		}
 		
 		return array($twitterTweetIds, $twitterTweetsById);
	}
	
	/**
	* adds html elements including links for urls, hashtag urls, mention urls, 
	* and images for twitpic, twitgoo, and yfrog images to the tweet text using the tweet entities 
	*
	* @param tweet text
	* @return tweet text html
	*/
	function convertTweetToHtml($tweet) {
				
		$htmlText = $tweet->text;
		
		$images = array();
		if( isset($tweet->entities) ) {
			if( isset($tweet->entities->urls) ) {
				foreach($tweet->entities->urls as $url) {
					
					// wrap anchor tag around urls 
					if( isset($url->expanded_url) ) {
						$htmlText = str_replace($url->url, '<a href="'.$url->expanded_url.'" target="_blank">'.$url->expanded_url.'</a>', $htmlText);
					} else {
						$htmlText = str_replace($url->url, '<a href="'.$url->url.'" target="_blank">'.$url->url.'</a>', $htmlText);
					}

					// get hosted images //
					
					// old twitpic 
					if( isset($url->url) && preg_match('/http:\/\/twitpic.com\//', $url->url) ) {
						$imageId = str_replace('http://twitpic.com/', '', $url->url);
						$imageUrl = 'http://twitpic.com/show/thumb/'.$imageId;
						if(self::checkIfImage($imageUrl)) {
							$images[] = '<a href="'.$url->url.'" target="_blank"><img src="'.$imageUrl.'" height="150" width="150" style="float:left;padding-right:10px;" /></a>';
						}
					// new twitpic
					} elseif( isset($url->expanded_url) && preg_match('/http:\/\/twitpic.com\//', $url->expanded_url) ) {								
						$imageId = str_replace('http://twitpic.com/', '', $url->expanded_url);
						$imageUrl = 'http://twitpic.com/show/thumb/'.$imageId;
						if(self::checkIfImage($imageUrl)) {
							$images[] = '<a href="'.$url->expanded_url.'" target="_blank"><img src="'.$imageUrl.'" height="150" width="150" style="float:left;padding-right:10px;" /></a>';
						}
					// old yfrog
					} elseif( isset($url->url) && preg_match('/http:\/\/yfrog.com\//', $url->url) ) {
						$imageUrl = $url->url.':small';
						if(self::checkIfImage($imageUrl)) {
							$images[] = '<a href="'.$url->url.'" target="_blank"><img src="'.$imageUrl.'" height="90" width="125" style="float:left;padding-right:10px;" /></a>';
						}
					// new yfrog
					} elseif( isset($url->expanded_url) && preg_match('/http:\/\/yfrog.com\//', $url->expanded_url) ) {
						$imageUrl = $url->expanded_url.':small';						
						if(self::checkIfImage($imageUrl)) {
							$images[] = '<a href="'.$url->expanded_url.'" target="_blank"><img src="'.$imageUrl.'" height="90" width="125" style="float:left;padding-right:10px;" /></a>';
						}
					// old twitgoo
					} elseif( isset($url->url) && preg_match('/http:\/\/twitgoo.com\//', $url->url) ) {						
						$imageUrl = $url->url.'/thumb';
						if(self::checkIfImage($imageUrl)) {
							// added defined size div tag b/c images are rectangles
							$images[] = '<div style="height:160px;width:160px;display:inline;"><a href="'.$url->url.'" target="_blank"><img src="'.$imageUrl.'" style="float:left;padding-right:10px;" /></a></div>';
						}
					// new twitgoo
					} elseif( isset($url->expanded_url) && preg_match('/http:\/\/twitgoo.com\//', $url->expanded_url) ) {
						$imageUrl = $url->expanded_url.'/thumb';					
						if(self::checkIfImage($imageUrl)) {
							// added defined size div tag b/c images are rectangles
							$images[] = '<div style="height:160px;width:160px;display:inline;"><a href="'.$url->expanded_url.'" target="_blank"><img src="'.$imageUrl.'" style="float:left;padding-right:10px;" /></a></div>';
						}
					}
				}
			}
			
			// get twitter hosted images and url's for those images
			if( isset($tweet->entities->media) ) {
				foreach($tweet->entities->media as $media) {
					if($media->type == 'photo') {
						$imageUrl = $media->media_url.':thumb';
						// no need to check if its an image b/c the json already tells us its an image
						$images[] = '<a href="'.$media->expanded_url.'" target="_blank"><img src="'.$imageUrl.'" height="150" width="150" style="float:left;padding-right:10px;" /></a>';
						// not showing expanding url b/c its go long
						$htmlText = str_replace($media->url, '<a href="'.$media->expanded_url.'" target="_blank">'.$media->url.'</a>', $htmlText);
					}	
				}
			}
			
			// add anchor tag for hashtags
			if( isset($tweet->entities->hashtags) ) {	
				foreach($tweet->entities->hashtags as $hashtag) {
					$htmlText = str_replace('#'.$hashtag->text, '<a href="http://twitter.com/#!/search?q=%23'.$hashtag->text.'" target="_blank">#'.$hashtag->text.'</a>', $htmlText);
				}
			}
			
			// add anchor tag for mentions
			if( isset($tweet->entities->user_mentions) ) {
				foreach($tweet->entities->user_mentions as $userMention) {
					$htmlText = str_replace('@'.$userMention->screen_name, '<a href="http://twitter.com/#!/'.$userMention->screen_name.'" target="_blank">@'.$userMention->screen_name.'</a>', $htmlText);
				}
			}
			
			// add location to tweet
			if( isset($tweet->place) && isset($tweet->place->full_name) ) {
				$htmlText .= '<br/>' . $tweet->place->full_name;
				if( isset($tweet->place->country_code) ) {
					$htmlText .= ', ' .$tweet->place->country_code;	
				}
			}
		}
		
		// add paragraph tag
		$htmlText = '<p id="tweetmap-text">' . $htmlText . '</p>';
		
		// add images to html text
		if(count($images) > 0) {
			$htmlText = '<br style="clear:both" />' . $htmlText;
			foreach($images as $image) {
				$htmlText = $image . $htmlText;
			}
		}
		
		return $htmlText;
	}
	
	/**
	* generates shortcode to create google map with tweets
	*
	* @param shortcode attributes from the user
	* @return html for generating the tweet map
	*/
	function shortcodeHandler($attrs) {
		global $wpdb;
		
		self::$add_script = true;
		
		self::$instanceCount++;
		
		$tweets = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.self::TWEET_MAP_TABLE.' ORDER BY tweet_id DESC');
	
		return TweetMapGenerateMap::generateMap($attrs, $tweets, get_option('tweetmap_twitter_username'), get_option('tweetmap_custom_css'), self::$instanceCount);
	}

	/*
	 * 
	 */
	function wpEnqueueScripts() {
		wp_register_script('google_maps', 'http://maps.googleapis.com/maps/api/js?sensor=false', false, '3', true);
		wp_register_script('styled-marker', plugins_url('js/StyledMarker.js', __FILE__), array('google_maps'), '0.5', true);
		wp_register_script('tweet_map', plugins_url('js/tweetmap.js', __FILE__), array('jquery', 'google_maps', 'styled-marker'), '0.9.4', true);

		wp_register_style('tweetmap', plugins_url('css/tweetmap.css', __FILE__), array(), '0.9.4', 'screen');
	}
	
	/**
	 * 
	 */
	function wpHead() {
		wp_enqueue_style('tweetmap');
	}
	
	/**
	* Decides whether the javascript files with be included in a page
	*/
	function wpFooter() {
		if ( ! self::$add_script )
		return;
	
		if(get_option('tweetmap_google_api_ssl') == 'true' || get_option('tweetmap_use_google_api_key') == 'true') {

			wp_deregister_script('google_maps');
			
			$protocol = 'http';
			if(get_option('tweetmap_google_api_ssl') == 'true') {
				$protocol = 'https';								
			}
			
			$urlKey = '';
			if(get_option('tweetmap_use_google_api_key') == 'true') {
				$urlKey = '&key='.get_option('tweetmap_google_api_key');
			}
			
			$googleMapApiUrl = $protocol.'://maps.googleapis.com/maps/api/js?sensor=false'.$urlKey;
			
			wp_register_script('google_maps', $googleMapApiUrl, false, '3', true);
		}
		
		
		// used print instead of enqueue b/c it doesnt work when printing the js bases on a variable 
		wp_print_scripts('google_maps');
		wp_print_scripts('styled-marker');
		wp_print_scripts('tweet_map');
	}

	/**
	* check if url is an image
	* 
	* @param url to check
	* @return url is an image true/false
	*/
	function checkIfImage($url) {
		// suppress warning causes it doesnt matter to the user
		$size = @getimagesize($url);
		if($size) {
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	* install plugin including settings and database table
	*/
	function install() {
		
		register_setting('tweetmap-settings-group', 'tweetmap_twitter_username');		
		register_setting('tweetmap-settings-group', 'tweetmap_max_tweets');
		register_setting('tweetmap-settings-group', 'tweetmap_custom_css');
		register_setting('tweetmap-settings-group', 'tweetmap_google_api_key');
		register_setting('tweetmap-settings-group', 'tweetmap_use_google_api_key');
		register_setting('tweetmap-settings-group', 'tweetmap_google_api_ssl');
		
		// default maximum number of tweets
		update_option('tweetmap_max_tweets', self::DEFAULT_MAX_TWEETS );
		
		self::createTweetMapTable();
	}
	
	/**
	* Setup database table for saving the tweets
	*/
	function createTweetMapTable() {
		global $wpdb;
		
		$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix.self::TWEET_MAP_TABLE;
		$wpdb->query($sql);
		
   		if($wpdb->get_var("show tables like '".$wpdb->prefix.self::TWEET_MAP_TABLE."'") != $wpdb->prefix.self::TWEET_MAP_TABLE) {
   			
   			$sql = "CREATE TABLE " . $wpdb->prefix.self::TWEET_MAP_TABLE . " (
   				id INT AUTO_INCREMENT PRIMARY KEY,
   				tweet_id BIGINT(20) NOT NULL,
   				text VARCHAR(140) NOT NULL,
   				html_text VARCHAR(1000) NOT NULL,
   				latitude decimal(12,9) NOT NULL,
   				longitude decimal(12,9) NOT NULL,
   				created_at VARCHAR(48) NOT NULL,
   				INDEX idx_tweet_id (tweet_id));";
   			
   			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
   		}	
	}

	/**
	* drop the tweetmap table
	*/
	function dropTweetMapTable() {
		global $wpdb;
		
		$sql = "DROP TABLE IF EXISTS ". $wpdb->prefix.self::TWEET_MAP_TABLE;
		$wpdb->query($sql);
	}
	
	/**
	* Creates sync tweet map cron job
	*/
	function scheduleCron() {
		wp_schedule_event(time(), 'hourly', 'hourly_tweetmap_sync' );
	}
	
	/**
	* Removes sync tweet map cron job
	*/
	function unscheduleCron() {
		wp_clear_scheduled_hook( 'hourly_tweetmap_sync' );
	}
	
}

?>