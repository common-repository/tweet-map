<?php function tweetmap_settings() { ?>

	<script type="text/javascript">

		var $j = jQuery.noConflict();

		$j(function(){

			$j('#use_google_api_key').click(function() {
				if( $j('#use_google_api_key').attr('checked') ) {
					$j('#google_api_key').removeAttr('readonly');
				} else {
					$j('#google_api_key').attr('readonly', true);
				}
			});
		});

	</script>

	<form name="latitude_settings" method="post">

		<p>
			<input type="submit" name="submit" value="Sync Tweets" />
		</p>
		<br/>
		<table>
			<tr>
				<td><span style="color:red;">*</span>&nbsp;<label for="twitter_username">Twitter Username:</label> </td>
				<td>
					<input type="text" name="twitter_username" id="twitter_username" class="regular-text" value="<?php echo get_option('tweetmap_twitter_username'); ?>" style="width:10em;" />
				</td>
			</tr>
			<tr>
				<td><span style="color:red;">*</span>&nbsp;<label for="max_tweets">Maximum Number of Geotagged Tweets:</label> </td>
				<td>
					<input type="text" name="max_tweets" id="max_tweets" class="regular-text" value="<?php echo get_option('tweetmap_max_tweets'); ?>" maxlength="2" style="width:4em;" />
				</td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;<label for="google_api_key">Google API Key:</label> </td>
				<td>
					<input type="text" name="google_api_key" id="google_api_key" class="regular-text" value="<?php echo get_option('tweetmap_google_api_key'); ?>" <?php if(get_option('tweetmap_use_google_api_key') != 'true') { echo 'readonly="readonly"'; } ?>/>
				</td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;<label for="use_google_api_key">Use Google API Key:</label> </td>
				<td>
					<div style="float:left;"><input type="checkbox" name="use_google_api_key" id="use_google_api_key" value="true" <?php if(get_option('tweetmap_use_google_api_key') == 'true') { echo 'checked="checked"'; } ?> /></div>
					<div style="float:right;"><a href="https://code.google.com/apis/console" target="_blank">get google api key</a></div>
				</td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;<label for="google_api_ssl">Google Maps over SSL:</label> </td>
				<td>
					<input type="checkbox" name="google_api_ssl" id="google_api_ssl" value="true" <?php if(get_option('tweetmap_google_api_ssl') == 'true') { echo 'checked="checked"'; } ?> />
				</td>
			</tr>		
			<tr>
				<td>&nbsp;&nbsp;<label for="custom_css">Custom CSS for Map Div:</label> </td>
				<td>
					<textarea name="custom_css" id="custom_css" class="regular-text" style="width:300px;height:120px;"><?php echo get_option('tweetmap_custom_css'); ?></textarea>
				</td>
			</tr>
		</table>
		
		<p>
			<input type="submit" name="submit" value="Update Options" />
		</p>
		<span style="color:red;">*</span> required settings
		<br/>
		<p style="font-weight:bold;">ShortCode Options for [tweet-map /]</p>
		<ul style="list-style-type:circle;padding-left:20px;">
			<li>height = height of the map in pixels</li>
			<li>width = width of the map in pixels</li>
			<li>max = maximum number of tweets to display on the map</li>
			<li>maptype = Google map type ( HYBRID, ROADMAP, SATELLITE, TERRAIN )</li>
			<li>mapcontrol = show map type control ( true / false )</li>
			<li>controlstyle = map control style ( DEFAULT, DROPDOWN_MENU, HORIZONTAL_BAR )</li>
			<li>zoomcontrol = show zoom controls ( true / false )</li>
			<li>zoomstyle = zoom control style ( DEFAULT, SMALL, LARGE )</li>
			<li>pancontrol = show pan control ( true / false )</li>
			<li>streetcontrol = show steet view control ( true / false )</li>
			<li>haveoverview = include overview map button ( true / false )</li>
			<li>openoverview = open overview map ( true / false )</li>
			<li>disabledefaultui = disable default UI ( true / false )</li>
			<li>showpath = show path between the tweets ( true / false )</li>
			<li>pathcolor = color of the path ( HTML color codes )</li>
			<li>pathweight = weight of the path in pixels</li>
			<li>pathopacity = opacity of the path ( 0.0 - 1.0 )</li>
			<li>currentcolor = color of the marker for the latest tweet ( HTML color codes )</li>
			<li>pastcolor = color of the marker for past tweets ( HTML color codes )</li>
			<li>style = add css style to the map div, it will override the custom css in the plugin settings</li>
		</ul>
	</form>


<?php } ?>