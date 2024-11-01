=== Tweet Map ===
Contributors: peter2322
Donate link: http://worldtravelblog.com/code/tweet-map-plugin
Tags: google, maps, twitter, twitpic, yfrog, twitgoo, tweet, geo tagged
Requires at least: 3.1.3
Tested up to: 3.2.1
Stable tag: 0.9.4

This plugin displays your geo tagged Twitter tweets on Google maps and also shows your Twitter, Twitpic, Twitgoo, and yfrog photos.

== Description ==

The Tweet Map plugin provides shortcode to display your geo tagged Twitter tweets on a customizable Google map. Your pictures uploaded to twitter, twitpic, twitgoo, and yfrog will be display in the map's info window. The plugin runs on a hourly wordpress cron to keep your tweets up to date. Just use [tweet-map /] on any post or page to see a map of your tweets. 

== Installation ==

1. Upload the folder containing the plugin files.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Update the options with your twitter username.
4. \[Optional\] Get a Google Simple API key at [https://code.google.com/apis/console](https://code.google.com/apis/console "Google API Key"). The api key tracks usuage and is not necessary. If your website uses a SSL certificate then set Google Maps to use SSL.
4. Click sync tweets.
5. Add [tweet-map /] to any page to see your map.
6. Your tweets will update automatically every hour.

== Shortcode Options ==

* height = height of the map in pixels
* width = width of the map in pixels
* max = maximum number of tweets to display on the map
* maptype = Google map type ( HYBRID, ROADMAP, SATELLITE, TERRAIN )
* mapcontrol = show map type control ( true / false )
* controlstyle = map control style ( DEFAULT, DROPDOWN_MENU, HORIZONTAL_BAR )
* zoomcontrol = show zoom controls ( true / false )
* zoomstyle = zoom control style ( DEFAULT, SMALL, LARGE )
* pancontrol = show pan control ( true / false )
* streetcontrol = show steet view control ( true / false )
* haveoverview = include overview map button ( true / false )
* openoverview = open overview map ( true / false )
* disabledefaultui = disable default UI ( true / false )
* showpath = show path between the tweets ( true / false )
* pathcolor = color of the path ( HTML color codes )
* pathweight = weight of the path in pixels
* pathopacity = opacity of the path ( 0.0 - 1.0 )
* currentcolor = color of the marker for the latest tweet ( HTML color codes )
* pastcolor = color of the marker for past tweets ( HTML color codes )
* style = add css style to the map div, it will override the custom css in the plugin settings

== Usage ==

* [tweet-map /] = standard
* [tweet-map height="300" width="500" /] = change the map height and width
* [tweet-map maptype="SATELLITE" /] = show a satellite map
* [tweet-map currentcolor="#00FF09" pastcolor="#FFFF00" /] = change the latest tweet marker to green and the past tweets markers to yellow
* [tweet-map max="80" /] = increase the maximum number of tweets to 80
* [tweet-map mapcontrol="false" /] = remove the map type control 
* [tweet-map pathcolor="FF0000" pathopacity="0.5" pathweight="3" /] = change the path color, opacity, and the weight
* etc etc

== Frequently Asked Questions == 

= What is wrong with my info windows = 

The info windows are inheriting CSS styles from the page. Please override the offending styles by using the custom css option on the plugin settings page.

= Where can I find HTML color codes =

[http://html-color-codes.info/](http://html-color-codes.info/ "HTML color codes")

= How often are my tweets updated =

The tweets are updated hourly by a Wordpress cron

= Why are some of my pictures not appearing =

The twitter image hosters often use proxies to store the photos and sometimes the proxy url has become stale leading to a missing image.

= Do I need to use the Google Simple API key? =

The Google Simply API key allows you to monitor to your usuage. This is important for highly trafficked websites that might approach the limits of the free service. For more information about usuage limits please visit [http://code.google.com/apis/maps/documentation/javascript/usage.html#usage_limits](http://code.google.com/apis/maps/documentation/javascript/usage.html#usage_limits "Usuage Limits") 

= Do I need to use Google Maps over SSL? = 

If you are using this plugin on a website that uses an SSL certificate, your url will start with "https://", then yes.


== Screenshots ==

1. Tweet Map settings
2. Example of a Tweet Map
3. Second example of a Tweet Map

== Changelog ==

= 0.9.4 =

* fixed yfrog bug
* fixed issue with info window size
* fixed info window max-width issue

= 0.9.3 =

* Updated Google Map url
* Added option to use Google Simple API key with Google Maps
* Added option to use Google maps over SSL
* Added shortcode option to add local style to the Google map
* Added ability to use multiple Tweet Maps on a single page

= 0.9.2 =

* Added support for displaying Twitter hosted images
* added check for no tweets
* Fixed map zoom without points

= 0.9.1 = 

* fixed twitgoo image bug
* increased default map height from 400px to 425px

= 0.9 = 

* Initial checkin

== Upgrade Notice ==

