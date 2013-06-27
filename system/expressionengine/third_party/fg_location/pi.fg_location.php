<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * FG Location Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		Michael Roling
 * @link		http://www.michaelroling.com
 */

$plugin_info = array(
	'pi_name'		=> 'FG Location',
	'pi_version'	=> '1.0.4',
	'pi_author'		=> 'Michael Roling',
	'pi_author_url'	=> 'http://www.michaelroling.com',
	'pi_description'=> 'Display latest Foursquare location information',
	'pi_usage'		=> Fg_location::usage()
);


class Fg_location {

	public $return_data;
	var $cache_path;
	var $cache_time;

	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->cache_path = APPPATH . 'cache/fg_location/';
	
		$this->cache_time = 300;
	}
	
	public function foursquare()
	{
		if(trim($this->EE->TMPL->fetch_param('private_rss','')) != '')
		{
			global $TMPL;
			$label="foursquare" . md5($this->EE->TMPL->fetch_param('private_rss',''));

			if($data = $this->get_cache($label))
			{
				$variable_row = json_decode($data,true);	
				return  $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $variable_row);
			} 
			else
			{
				$data = $this->get_foursquare_location();
				$this->set_cache($label, $data);
				$variable_row = json_decode($data,true);		
				return $this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $variable_row);
			}
		}
	}	
	
	private function get_foursquare_location()
	{
		$private_rss = $this->EE->TMPL->fetch_param('private_rss','');
		
		$google_map_width = $this->EE->TMPL->fetch_param('map_width','100');
		$google_map_height = $this->EE->TMPL->fetch_param('map_height','100');
		$google_map_marker_color = $this->EE->TMPL->fetch_param('map_marker_color','blue');
				
		$s = @file_get_contents($private_rss . "?count=1");
		$parser = simplexml_load_string($s);
		 
		if(empty($parser->channel->item[0]->guid))
		{
			return false;
		}
		
		else
		{
			foreach($parser->channel->item as $entry)
			{
				$location_name = (string)$entry->title;
				
		
				$ns_geo = $entry->children('http://www.georss.org/georss');
				$long_lat = str_replace(" ",",",trim($ns_geo->point));
				
				$google_map = $this->get_google_map($long_lat,$google_map_width,$google_map_height,$google_map_marker_color);
				$google_map_link = $this->get_google_map_link($long_lat);
				
			
				list ($city, $state) = $this->get_physical_location($long_lat);
								
				$checkin_url = (string)$entry->link; 
				
				$data_output = json_encode(array('location_name'=>htmlentities($location_name,ENT_QUOTES, "UTF-8"),'city'=>htmlentities($city,ENT_QUOTES, "UTF-8"),'state'=>htmlentities($state,ENT_QUOTES, "UTF-8"),'checkin_link'=>$checkin_url,'coordinates'=>$long_lat,'google_map'=>$google_map,'google_map_link'=>$google_map_link));
				return $data_output;
				break;
			}
		}
	}		
	
	private function get_google_map($long_lat, $width=100, $height=100, $color='blue')
	{
		$color = urlencode($color);
		return "http://maps.google.com/maps/api/staticmap?center=" . $long_lat . "&zoom=12&size=" . $width . "x" . $height . "&sensor=false&markers=color:" . $color . "%7C" . $long_lat;
	}
	
	private function get_google_map_link($long_lat)
	{
		return "http://maps.google.com/maps?f=q&source=s_q&hl=en&geocode=&q=" . $long_lat . "&aq=&sll=" . $long_lat . "&ie=UTF8&z=11";
	}

	private function get_physical_location($long_lat)
	{
		$g = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?latlng=". $long_lat . "&sensor=false");

		$google_parser = json_decode($g);

		if(empty($google_parser->results[0]))
		{
			return false;
		}		
		
		$k=0;
		
		$city="";
		$state="";
		
		foreach($google_parser->results[0] as $adr_cmpt)
		{
			if(is_array($adr_cmpt))
			{
				foreach($adr_cmpt as $adr_cmpt_item)
				{
					if(isset($adr_cmpt_item->types))
					{	
						if($adr_cmpt_item->types[0]=="administrative_area_level_1")
						{
							$state = $adr_cmpt_item->short_name;
						}
						if($adr_cmpt_item->types[0]=="administrative_area_level_3" && empty($city)==true)
						{
							$city = $adr_cmpt_item->short_name;
						}
						if($adr_cmpt_item->types[0]=="locality")
						{ 
							$city = $adr_cmpt_item->short_name;
						}														
					}
				}
			}
		}	
		return array ($city, $state);
	}
		
	private function set_cache($label, $data)
	{
		if(! is_dir($this->cache_path))
		{
			mkdir($this->cache_path,0777);
		}
		
		file_put_contents($this->cache_path . $this->safe_filename($label) .'.cache', $data);
	}
	
	private function get_cache($label)
	{
		if($this->is_cached($label))
		{
			$filename = $this->cache_path . $this->safe_filename($label) .'.cache';
			return file_get_contents($filename);
		}
		return false;
	}
	
	private function is_cached($label)
	{
		$filename = $this->cache_path . $this->safe_filename($label) .'.cache';
		if(file_exists($filename) && (filemtime($filename) + $this->cache_time >= time()))
		{
			return true;
		}
	
		return false;
	}
	
	private function safe_filename($filename)
	{
		return preg_replace('/[^0-9a-z\.\_\-]/i','', strtolower($filename));
	}

	// ----------------------------------------------------------------
	
	public static function usage()
	{
		ob_start();
?>
FG Location is an ExpressionEngine 2.x plugin that returns the last check-in information 
for Foursquare users and also returns a Google map with a marker on the last
location.  The data is cached for five minutes within the expressionengine/cache
directory to increase page load performance and for high traffic sites to stay within
the Foursquare API rate limits.

Foursquare
{exp:fg_location:foursquare}
	 ...single variables 
{/exp:fg_location:foursquare}


========================================================================================
Example Usage
========================================================================================

{exp:fg_location:foursquare private_rss="URL TO PRIVATE FEED" map_height="100" map_width="100" map_marker_color="blue"}
	<p><a href="{google_map_link}"><img src="{google_map}" /></a>
	<a href="{checkin_link}">{location_name}</a><em>{city}, {state}</em></p>
{/exp:fg_location:foursquare}


========================================================================================
Parameters
========================================================================================

private_rss (Foursquare, required)
 - The private rss URL to the user's checkin history. This URL can be obtained by going
   to https://foursquare.com/feeds/ and following the instructions.
 - Example: private_rss="URL TO PRIVATE FEED"
 
map_height
 - The Google map height.
 - Example: map_height="100"

map_width
 - The Google map width.
 - Example: map_width="100" 

map_marker_color
 - The Google map marker color.
 - You can use 24 bit color (0xFFFFCC) or a predefined color from the set (black, brown,
   green, purple, yellow, blue, gray, orange, red, white).
 - Example: map_marker_color="blue"
 

========================================================================================
Single Variables
========================================================================================

{location_name}
 - The name of the location entity.
 
{city}
 - The city name of the location.
 
{state}
 - The state name of the location.
 
{coordinates}
 - The coordinates (long,lat) of the location.
 
{checkin_link}
 - The URL to the checkin on the third party site.
 
{google_map}
 - The URL to the Google map image of the location.
 
{google_map_link}
 - The URL to the Google maps service. 
 
 
========================================================================================
Requirements
========================================================================================

Web server writable /system/expressionengine/cache directory.

PHP 5.2+
 
 
========================================================================================
Notes
========================================================================================

If the plugin isn't working after install, make sure that the /system/expressionengine/cache
directory is writeable by the web server.

When modifying the map width and height variables, you may need to empty the cache to see the changes.
 
========================================================================================
License Agreement
========================================================================================

http://creativecommons.org/licenses/by/3.0/
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.fg_location.php */
/* Location: /system/expressionengine/third_party/fg_location/pi.fg_location.php */