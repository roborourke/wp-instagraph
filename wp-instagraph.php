<?php
/*
Plugin Name: Instagraph
Plugin URI:
Description: Use Instagram style filters on your thumbnails
Author: Robert O'Rourke, Richard Tape
Version: 0.1
Author URI:
*/

// get class library
require_once( 'includes/instagraph.php' );


class WP_Instagraph extends Instagraph {

	var $filter;

	var $filters = array( 'lomo', 'nashville', 'kelvin', 'toaster', 'gotham', 'tilt_shift' );

	function __construct( $image = false, $output = false ) {

		// set up filters for this instance
		$this->filters = apply_filters( 'instagraph_filters', $this->filters );

		// set the parent class up
		if ( $image && $output )
			parent::__construct( $image, $output );

	}

	function downsize( $out, $id, $size ) {

		$img_url = wp_get_attachment_url( $id );
		$img_path = get_attached_file( $id );
		$meta = wp_get_attachment_metadata( $id );
		$width = $height = 0;
		$is_intermediate = false;
		$img_url_basename = wp_basename( $img_url );
		$img_path_basename = wp_basename( $img_path );

		// extract filter from size request
		$size_bits = explode( ':', $size );
		$filter = isset( $size_bits[ 1 ] ) ? $size_bits[ 1 ] : false;
		$size 	= isset( $size_bits[ 0 ] ) ? $size_bits[ 0 ] : false;

		// start the reactor
		if ( $filter ) {

			// try for a new style intermediate size
			if ( $intermediate = image_get_intermediate_size($id, $size) ) {
				$img_url = str_replace($img_url_basename, $intermediate['file'], $img_url);
				$img_path = str_replace($img_path_basename, $intermediate['file'], $img_path);
				$width = $intermediate['width'];
				$height = $intermediate['height'];
				$is_intermediate = true;
			}
			elseif ( $size == 'thumbnail' ) {
				// fall back to the old thumbnail
				if ( ($thumb_file = wp_get_attachment_thumb_file($id)) && $info = getimagesize($thumb_file) ) {
					$img_url = str_replace($img_url_basename, wp_basename($thumb_file), $img_url);
					$img_path = str_replace($img_path_basename, wp_basename($thumb_file), $img_path);
					$width = $info[0];
					$height = $info[1];
					$is_intermediate = true;
				}
			}
			if ( !$width && !$height && isset($meta['width'], $meta['height']) ) {
				// any other type: use the real image
				$width = $meta['width'];
				$height = $meta['height'];
			}

			if ( $img_url && $img_path ) {

				$input = $img_path;
				$output = $this->filtered_url( $input, $filter );

				// generate filtered thumb
				if ( ! file_exists( $output ) )
					$this->filter( $filter, $input, $output );

				// point to our new file
				$img_url = $this->filtered_url( $img_url, $filter );

				// we have the actual image size, but might need to further constrain it if content_width is narrower
				list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

				return array( $img_url, $width, $height, $is_intermediate );
			}

			// don't continue the downsize funtion
			return true;
		}

		return $out;
	}

	function filtered_url( $url, $filter ) {
		return preg_replace( "/(\.[^\.]+)$/", ".{$filter}$1", $url );
	}

	// run the image filter
	function filter( $filter = 'nashville', $input, $output ) {
		try {
			$instagraph = new WP_Instagraph( $input, $output );

			if( ! in_array( $filter, apply_filters( 'instagraph_filters', $instagraph->filters ) ) )
				$filter = 'nashville'; // if method not in array, default it

			$instagraph->$filter(); // name of the filter from class
		}
		catch (Exception $e) {
			wp_die( $e->getMessage(), __( 'Instagraph plugin error' ) );
		}
	}

	// allow addition of custom filters
	function __call( $method, $args ) {
		do_action( "instagraph_custom_$method", $this );
	}

}



// big daddy filter that all image requests go through, run it after everything else though
$wp_instagraph = new WP_Instagraph();
add_filter( 'image_downsize', array( $wp_instagraph, 'downsize' ), 100000000, 3 );


/**
 * Add new filters to instagraph
 *
 * @param String $filter   A name for the filter
 * @param Function $callback 	A callback function that gets passed the instagraph object as its parameter
 *
 * @return null
 */
function register_instagraph_filter( $filter, $callback ) {
	add_filter( "instagraph_filters", create_function( '$filters', 'return array_filter( array_merge( $filters, array( "' . $filter . '" ) ) );' ) );
	add_action( "instagraph_custom_$filter", $callback, 10, 2 );
}

?>
