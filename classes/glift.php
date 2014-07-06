<?php

/** This Glift object is basically a wrapper for glift.js and uses
 * the same nomenclature. The main purpose of the object is to validate data
 * and structure it for output in JSON.
 */

class Glift implements JsonSerializable {

	protected $divId;
	protected $sgf;
	protected $sgfCollection;
	protected $allowWrapAround;
	protected $sgfDefaults;
	protected $display;
	/* ADD ANY NEW GLIFT PROPERTIES HERE (and in glift_eat_shortcode below)*/

	public function __construct( $properties = array( 'sgf' => '' ) ) {

		static $id = 1; // keep track of unique div id 
		if ( is_int( $id ) ) $this->divId = ( "glift_display$id" );
		$id++;
		
		// add other properties from function parameter
		$this->add_properties( $properties );
	}


	// required by objects that implement JsonSerializable
	// this method will be accessed by json_encode()
	public function jsonSerialize() {
	
		// convert this object to an array
		$glift_data = get_object_vars( $this );

		// escape all array elements for output and drop any null properties
		$glift_data = glift_mega_map( 'glift_escape', $glift_data, array() );

		return $glift_data;
	}


	// Add properties to this object
	private function add_properties( $properties = array( 'sgf' => '' ) ) {
		
		// Validate SGF data. If we have no data, set to zero length string.
		if ( array_key_exists( 'sgf', $properties ) ) {
			
			if ( glift_is_url( $properties['sgf'] ) ) {
				$this->assign_url( $properties['sgf'] );

			} elseif ( glift_is_sgf( $properties['sgf'] ) ) {
				$this->sgf = ( $properties['sgf'] );
				
			} elseif ( is_array ( $properties['sgf'] ) ) {		
				$this->sgfCollection = $properties['sgf'];
			
			} else {
				// we have data, but it's invalid, set sgf set to ZLS
				$this->sgf = '';
			} // end of sgf if block

		// if we don't have $sgf, try $sgfCollection
		} elseif ( $array_key_exists( 'sgfCollection', $properties ) ) {

			if ( glift_is_url( $properties['sgfCollection'] ) ) {
			$this->assign_url( $properties['sgfCollection'] );

			} elseif ( is_array( $properties['sgfCollection'] ) ) {		
				$this->sgfCollection = $properties['sgfCollection'];
			
			} else {
				$this->sgf = '';
			} // end of sgfCollection if block

		} else {
			// we have no sgf data, so set sgf to zero length string
			$this->sgf = '';
		
		} // end of SGF data if block

		// we've finished with sgf and sgfCollection, so drop them from array
		unset( $properties['sgf'], $properties['sgfCollection'] );
		
		// set remaining properties to value that was passed
		foreach( $properties as $key => $value ) {
			$this->$key = $properties[$key];
		}
	}


	// check the file extension of an 'sgf' url and assign it to a property
	private function assign_url( $url ) {

		switch ( glift_get_filetype( $url ) ) {
			case 'sgf':
				$this->sgf = ( $url );
				break;
			case 'json':
			default:
				$this->sgfCollection = ( $url );
		}
	}


	// JSON encodes Glift object and returns it as HTML for glift.js
	public function to_html() {
		#TODO(dormerod): move div style info to somewhere more reusable

		$divId = esc_attr( $this->divId );
		$json = json_encode( $this, JSON_PRETTY_PRINT );
		$style = "height:500px; width:100%; position:relative;";
		$html =
				"<div id='$divId' style='$style'></div>
				&nbsp;
				<script type='text/javascript'>
				gliftWidget = glift.create($json);
				</script>";
		return $html;
	}


	// sanitize, explicitly validate and arrange shortcode data
	public function eat_shortcode( $atts, $content, $tag ) {

		// did our shortcode send any data?
		if ( $atts ) {
			// if so then clean it up
			$clean_atts = array_map( 'sanitize_text_field', $atts );

				// imma find me some sgf data! (O_o)
				if ( array_key_exists( 'sgf', $clean_atts ) ) {
					$properties['sgf'] = $clean_atts['sgf'];
				
				// no sgf data so far, do we have an sgfCollection then?
				} elseif ( array_key_exists( 'sgfcollection', $clean_atts ) ) {
					$properties['sgfCollection'] = $clean_atts['sgfcollection'];
				
				// still no sgf data, so do we have $content?
				// content is the data between the [glift]$content[/glift] tags
				// using a closing tag and providing content is optional
				} elseif ( $content ) {
					$clean_content = sanitize_text_field( $content );
					$properties['sgf'] = $clean_content;

				} else {
					// we don't have any sgf data, so return nothing
					return;
				}
		
				// explicitly grab any remaining shortcode attributes
				
				/* allowWraparound */
				$properties['allowWrapAround'] = 
				array_key_exists( 'allowwraparound', $clean_atts ) ?
				$clean_atts['allowwraparound'] : NULL;
				
				/* sgfDefaults */
				if ( array_key_exists( 'widgettype', $clean_atts ) )
				$sgfDefaults['widgetType'] = $clean_atts['widgettype'];
				$properties['sgfDefaults'] = isset( $sgfDefaults ) ?
				$sgfDefaults : NULL;

				/* display */
				if ( array_key_exists( 'theme', $clean_atts ) )
				$display['theme'] = $clean_atts['theme'];
				if ( array_key_exists( 'goboardbackground', $clean_atts ) )
				$display['goBoardBackground'] = 
				$clean_atts['goboardbackground'];
				$properties['display'] = isset( $display ) ? $display : NULL;

				/* ADD ANY NEW GLIFT PROPERTIES HERE */
				/* Note: shortcode $atts are always returned in lower case*/

		// we didn't receive any shortcode atts, so let's look for content
		} elseif ( $content ) {
			$clean_content = sanitize_text_field( $content );
			$properties['sgf'] = $clean_content;

		} else {
			// we don't seem to have any sgf data, so sgf = ZLS
			$properties['sgf'] = '';
		}

		// and finally, add the shortcode attributes as properties
		$this->add_properties( $properties );

		return;
	}
}
