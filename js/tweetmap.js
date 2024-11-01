var tweetMapInfowindows = new Array();

jQuery(document).ready(function() {
	jQuery('.tweetMapMap').each(function(){
		var index = jQuery(this).attr('id').split('_')[1];
		createTweetMap(index);
	});
});

function createTweetMap(index) {
	
	var attrs = tweetMapAttributes[index];
	
	var tweetMapOptions = {
		zoom: 1,
		center: new google.maps.LatLng(0,0),
		mapTypeId: google.maps.MapTypeId[attrs['maptype']],
		mapTypeControl: attrs['mapcontrol'],
		mapTypeControlOptions: {
		    style: google.maps.MapTypeControlStyle[attrs['controlstyle']]
		},	
		zoomControl: attrs['zoomcontrol'],
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle[attrs['zoomstyle']]
		},
		panControl: attrs['pancontrol'],
		streetViewControl: false,
		overviewMapControl : attrs['haveoverview'],
		overviewMapControlOptions: {
			opened : attrs['openoverview']
		},
		disableDefaultUI: attrs['disabledefaultui']
	};
	
	var map = new google.maps.Map(document.getElementById("tweetMapMap_"+index), tweetMapOptions);
	
	var bounds = new google.maps.LatLngBounds();
	var pathCoordinates = new Array();
	var markers = new Array();
	for(var i=0; i < tweetMapTweets[index].length; i++) {
		
		var color = attrs['pastcolor'];
		var last = false;
		if(i == 0) {
			color = attrs['currentcolor'];
		}
		var zIndex = tweetMapTweets[index].length - i;
		var marker = tweetMapCreateMarker(tweetMapTweets[index][i], zIndex, color, index, map);
		markers.push(marker);
		bounds.extend(marker.getPosition());
		if(i == attrs['max']-1) {
			break;
		}
	}
	
	tweetMapCreatePath(map, pathCoordinates, attrs);
	
	if(!bounds.isEmpty()) {
		map.fitBounds(bounds);
		map.setCenter(bounds.getCenter());
	}
}

function tweetMapCreatePath(map, pathCoordinates, attrs) {
	if(attrs['showpath'] == true) {
		var path = new google.maps.Polyline({
		      path: pathCoordinates,
		      strokeColor: attrs['pathcolor'],
		      strokeOpacity: 1.0,
		      strokeWeight: attrs['pathweight']
		});
		path.setMap(map);
	}
}

function tweetMapCreateMarker(tweet, zIndex, color, index, map) {

	var latlng = new google.maps.LatLng(tweet['latitude'],tweet['longitude']);
	var marker = new StyledMarker({
		styleIcon: new StyledIcon(
			StyledIconTypes.MARKER, { color: color }
		),
		position: latlng,
		title: tweet['text'],
		flat: true,
		zIndex: zIndex, 
		map: map,
		infowindowContent: '<div style="line-height:2em;padding-top:5px;text-align:left;">' + tweet['html_text'] + '<a href="http://twitter.com/#!/' + twitterUser[index] + '/status/' + tweet['tweet_id'] + '" target="_blank">' + timeAgo(tweet['created_at']) + '</a></div>'
	});		
	
	var maxWidth = map.getDiv().clientWidth - 80;
	
	tweetMapInfowindows[index] = new google.maps.InfoWindow({
		content: 'holding...',
		maxWidth: maxWidth
	});
		
	google.maps.event.addListener(marker, 'click', function() {
		tweetMapInfowindows[index].setContent(marker.infowindowContent);
		tweetMapInfowindows[index].open(map, this);
	});
		
	return marker;	
}

var browser = function() {
    var ua = navigator.userAgent;
    return { ie: ua.match(/MSIE\s([^;]*)/) };
}();

/**
 * relative time calculator
 * @param {string} twitter date string returned from Twitter API
 * @return {string} relative time like "2 minutes ago"
 */
var timeAgo = function(dateString) {
	var rightNow = new Date();
	var then = new Date(dateString);

	if (browser.ie) {
		// IE can't parse these crazy Ruby dates
		then = Date.parse(dateString.replace(/( \+)/, ' UTC$1'));
	}

	var diff = rightNow - then;

	var second = 1000,
    	minute = second * 60,
    	hour = minute * 60,
    	day = hour * 24,
    	week = day * 7;

	if (isNaN(diff) || diff < 0) { return ""; }
	if (diff < second * 2) { return "right now"; }
	if (diff < minute) { return Math.floor(diff / second) + " seconds ago"; }
	if (diff < minute * 2) { return "about 1 minute ago"; }
	if (diff < hour) { return Math.floor(diff / minute) + " minutes ago"; }
	if (diff < hour * 2) { return "about 1 hour ago"; }
	if (diff < day) { return  Math.floor(diff / hour) + " hours ago"; }
	if (diff > day && diff < day * 2) { return "yesterday"; }
	if (diff < day * 365) { return Math.floor(diff / day) + " days ago";
	} else { return "over a year ago"; }
};