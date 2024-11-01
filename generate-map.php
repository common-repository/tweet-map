<?php 

class TweetMapGenerateMap {

	function generateMap($attrs, $tweets, $twitterUser, $customStyle, $count) {
			
		$attributes = shortcode_atts( array(
			'height' => 400, // number for pixel
			'width' => 600, // number for pixel
			'max' => 30, // number of locations
			'maptype' => 'TERRAIN', // HYBRID, ROADMAP, SATELLITE, TERRAIN
			'mapcontrol' => 'true', //true/false
			'controlstyle'=> 'DROPDOWN_MENU', // DEFAULT, DROPDOWN_MENU, HORIZONTAL_BAR
			'zoomcontrol' => 'true', //true/false
			'zoomstyle' => 'SMALL', // DEFAULT, SMALL, LARGE
			'pancontrol' => 'false', // true/false
			'streetcontrol' => 'false', // true/false
			'haveoverview' => 'false', // true/false
			'openoverview' => 'false', // true/false
			'disabledefaultui' => 'false', //true/false
			'showpath' => 'false', // true/false
			'pathcolor' => 'black', // CSS3 colors
			'pathweight' => 1, // number of pixels
			'pathopacity' => 0.1, //0.0 - 1.0
			'currentcolor' => '#FF0000', // CSS3 colors in HEX, #FF0000 (red)
			'pastcolor' => '#6699ff', //  CSS3 colors in HEX, #6699ff (blue)\
			'style' => '' //css
		), $attrs);
		
		foreach($attributes as $key => $value) {
			$lcValue = strtolower($value);
			if( $lcValue == 'true' ) {
				$attributes[$key] = true;
			} elseif( $lcValue == 'false' ) {
				$attributes[$key] = false;
			}
		}
		
		if($attributes['style'] != '') {
			$customStyle = $attributes['style']; 
		}
		
		ob_start();
	
?>
		<div id="tweetMapMap_<?php echo $count; ?>" class="tweetMapMap" style="height:<?php echo $attributes['height']; ?>px; width:<?php echo $attributes['width']; ?>px;<?php echo $customStyle; ?>"></div>	
		<script type="text/javascript">
			<?php if($count == 1):?>
				var tweetMapTweets = new Array();
				var tweetMapAttributes = new Array();
				var twitterUser = new Array();
			<?php endif;?>
		
			tweetMapTweets[<?php echo $count; ?>] = <?php echo json_encode($tweets); ?>;
			tweetMapAttributes[<?php echo $count; ?>] = <?php echo json_encode($attributes); ?>;
			twitterUser[<?php echo $count; ?>] = "<?php echo $twitterUser; ?>";
		</script>

<?php 

		return ob_get_clean();
	}
} 

?>