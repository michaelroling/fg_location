fg_location
===========

FG Location is an ExpressionEngine 2.x plugin that returns the last check-in information for Foursquare users and also returns a Google map with a marker on the last location. The data is cached for five minutes within the expressionengine/cache directory to increase page load performance and for high traffic sites to stay within the Foursquare API rate limits.

To install, upload fg_location folder within /system/expressionengine/third_party/ and ensure /system/expressionengine/cache/ is writable by the web server.

## Example Usage
<pre><code>&#123;exp:fg_location:foursquare private_rss=&quot;URL TO PRIVATE FEED&quot; map_height=&quot;100&quot; map_width=&quot;100&quot; map_marker_color=&quot;blue&quot;&#125;
    &lt;p&gt;&lt;a href=&quot;&#123;google_map_link&#125;&quot;&gt;&lt;img src=&quot;&#123;google_map&#125;&quot; /&gt;&lt;/a&gt;
    &lt;a href=&quot;&#123;checkin_link&#125;&quot;&gt;&#123;location_name&#125;&lt;/a&gt;&lt;em&gt;&#123;city&#125;, &#123;state&#125;&lt;/em&gt;&lt;/p&gt;
&#123;/exp:fg_location:foursquare&#125;</code></pre>

## Parameters
* private_rss (Foursquare, required): The private rss URL to the user’s checkin history. This URL can be obtained by going to https://foursquare.com/feeds/ and following the instructions.
* map_height: The Google map height.
* map_width: The Google map width.
* map_marker_color: The Google map marker color.  You can use 24 bit color (0xFFFFCC) or a predefined color from the set (black, brown, green, purple, yellow, blue, gray, orange, red, white).

## Single Variables
* {location_name}: The name of the location entity.
* {city}: The city name of the location.
* {state}: The state name of the location.
* {coordinates}: The coordinates (long,lat) of the location.
* {checkin_link}: The URL to the checkin on the third party site.
* {google_map}: The URL to the Google map image of the location.
* {google_map_link}: The URL to the Google maps service.

## Requirements
Web server writable /system/expressionengine/cache directory.
PHP 5.2+

## Notes
If the plugin isn’t working after install, make sure that the /system/expressionengine/cache directory is writeable by the web server.
When modifying the map width and height variables, you may need to empty the cache to see the changes.

## Licensing
Plugin is licensed under [CC-BY-SA 3.0](http://creativecommons.org/licenses/by/3.0/) (Creative Commons Attribution-ShareAlike 3.0 Unported License).