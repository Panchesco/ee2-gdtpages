<?php
/**
* Gdtpages Class
*
* @package ExpressionEngine
* @author Richard Whitmer/Godat Design, Inc.
* @copyright (c) 2014, Godat Design, Inc.
* @license
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @link http://godatdesign.com
* @since Version 2.9
*/
 
 // ------------------------------------------------------------------------

/**
 * Good at Pages Plugin
 *
 * @package			ExpressionEngine
 * @subpackage		third_party
 * @category		Plugin
 * @author			Richard Whitmer/Godat Design, Inc.
 * @copyright		Copyright (c) 2014, Godat Design, Inc.
 * @link			http://godatdesign.com
 */
  
 // ------------------------------------------------------------------------

	$plugin_info = array(
	    'pi_name'         => 'Good at Pages',
	    'pi_version'      => '1.0',
	    'pi_author'       => 'Richard Whitmer/Godat Design, Inc.',
	    'pi_author_url'   => 'http://godatdesign.com/',
	    'pi_description'  => '
	    Get data about saved as part of the pages module.
	    ',
	    'pi_usage'        => Gdtpages::usage()
	);
	

	class  Gdtpages {
			
			public	$site_id			= 1;
			public	$pages_uri			= FALSE;
			public	$entry_id			= FALSE;
			public	$return_data		= '';
			public	$title				= NULL;
			public	$field				= array();
			public	$status_array		= array('open');
			public	$wrapper			= array();
			private	$pages_array		= array();
			private	$ids_array			= array();
			private	$field_names		= array();
			private	$field_ids			= array();

		
			public function __construct()
			{
			
				// Since we'll be using the URL helper, load it.
				ee()->load->helper('url');

				// Get info from the pages module about page uris
				$this->site_id			= ee()->config->item('site_id');
				$this->pages_array		= ee()->config->item('site_pages')[$this->site_id]['uris'];
				$this->ids_array		= array_flip($this->pages_array);
				$this->field_names		= $this->field_names();
				$this->field_ids		= array_flip($this->field_names);
				

				// Fetch the pages_uri.
				if(ee()->TMPL->fetch_param('pages_uri'))
				{
					// Make sure it's in the right format.
					$str				= ee()->TMPL->fetch_param('pages_uri');
					$this->pages_uri	= '/' . trim($str,'/');
				}
				
				// Fetch the field parameter.
				if(ee()->TMPL->fetch_param('field'))
				{
					$this->field = explode('|',ee()->TMPL->fetch_param('field'));
					
				}
				
				
				// Fetch the wrapper parameter.
				if(ee()->TMPL->fetch_param('wrapper'))
				{
					$this->wrapper = explode('|',ee()->TMPL->fetch_param('wrapper'));
					
				}

				// Set the entry_id.
				if($this->pages_uri)
				{
					if(isset($this->ids_array[$this->pages_uri]))
					{
						$this->entry_id = $this->ids_array[$this->pages_uri];
						
						$this->title = $this->current_title();
					}

				}
				
				$this->return_string();
			}
			
			
			/**
			 *	Concatenate requested fields into one string.
			 *	@return string
			 */
			 private function return_string()
			 {
				
				 if($this->title !== NULL)
				 {
				 	
				 	$i =0;
				 	foreach($this->field as $key)
				 	{
					 	
					 	if(isset($this->wrapper[$i]))
					 	{
						 	$this->return_data.=	'<' . $this->wrapper[$i] . '>';
					 	}
					 	
					 	$this->return_data.=	$this->title->{$key};
					 	
					 	// If more than one field was requested, insert the separator.
					 	if(isset($this->wrapper[$i]))
					 	{
						 	$this->return_data.=	'</' . $this->wrapper[$i] . '>';
					 	}
					 	
					 	$i++;

					}
					
					
				 }
			 }
			
			// ------------------------------------------------------------------------
			
			/**
			 *	Return data.
			 *	@return string
			 */
			 public function title()
			 {
			 	return $this->return_data;
			 }	
			 
			 // ------------------------------------------------------------------------
			 
			 /**
			 *	Return the channel_title entry url_title.
			 *	@return string
			 */
			 public function url_title()
			 {
			 	return ($this->title !== NULL) ? $this->title->url_title : '';
			 }	
			 
			 // ------------------------------------------------------------------------
			
			/** 
			 *	Get the channel_titles row.
			 *	@return object
			 */
			 private function current_title()
			 {
			 	 
			 	 $sel = array(
			 	 			'channel_titles.title',
			 	 			'channel_titles.url_title',
			 	 			'channel_titles.site_id',
			 	 			'channel_titles.channel_id',
			 	 			'channels.channel_name',
			 	 			'channels.channel_title',
			 	 			);
			 	 			
			 	 // Map field ids to human-friendly field names.		
			 	 foreach($this->field_names as $id=>$name)
			 	 {
				 	 $sel[]	= 'channel_data.' . $id . ' AS ' . $name;
			 	 }
			 
				 
				 $query = ee()->db
				 			->select($sel)
				 			->join('channels','channels.channel_id = channel_titles.channel_id')
				 			->join('channel_data','channel_data.entry_id = channel_titles.entry_id')
				 			->where('channel_titles.entry_id',$this->entry_id)
				 			->where_in('channel_titles.status',$this->status_array)
				 			->limit(1)
				 			->get('channel_titles');

				 if($query->num_rows()==1)
				 {
				 	return $query->row();
				 }
				 
			 }

			// ------------------------------------------------------------------------
			
			/**
			 *	Get associative array of field_id_{integer} => field_name values for custom fields.
			 *	@return array
			 */
			 private function field_names()
			 {
				 $data		= array();
				 $sel[]		= 'field_id';
				 $sel[]		= 'field_name';
				 
				 $fields		= ee()->db
				 					->select($sel)
				 					->order_by('field_id')
				 					->get('channel_fields')
				 					->result();
				 
				
				 foreach($fields as $key=>$row)
				 {
					 $key 			= 'field_id_' . $row->field_id;
					 $data[$key]	= $row->field_name;
				 }
				 
				 return $data;
			 }
			


			/**
			 *	Return plugin usage documentation.
			 *	@return string
			 */
			public function usage()
			{
				
					ob_start();  ?>
					
					
					ABOUT:
					----------------------------------------------------------------------------
					Uses the pages_uri field value from a channel entry to return formatted 
					title and custom field data for the entry using a single tag from anywhere
					in a template.
										
					
					TAGS:
					----------------------------------------------------------------------------
					{exp:gdtpages:title}
					
					
					
					REQUIRED PARAMETERS: 
					----------------------------------------------------------------------------
					pages_uri		- 	pages_uri value entered with the channel record
					field			-	pipe delimited list of properties and custom field names
					
					Example:
					{exp:gdtpages:title pages_uri="/about-us" field="title|blurb"}
					
					
					OPTIONAL PARAMETERS: 
					----------------------------------------------------------------------------
					wrapper			-	pipe dimited list of html tag names in which to wrap 
										the returned values
					Example:
					{exp:gdtpages:title pages_uri="/about-us" field="title|blurb" wrapper="h1|p"}
					

					<?php
					 $buffer = ob_get_contents();
					 ob_end_clean();
					
					return $buffer;
					
			}
			

	}
/* End of file pi.gdtpages.php */
/* Location: ./system/expressionengine/third_party/gdtpages/pi.gdtpages.php */
