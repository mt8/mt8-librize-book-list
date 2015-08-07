<?php
/*
	Plugin Name: MT8 librize Book List
	Plugin URI: https://github.com/mt8/mt8-librize-book-list
	Description: Show <a href="http://librize.com/">Librize</a> book list by shortcode.
	Author: mt8.biz
	Version: 1.0
	Author URI: http://mt8.biz
	Domain Path: /languages
	Text Domain: mt8-librize-book-list
*/

	$mt8lbl = new Mt8_Librize_Book_List();
	$mt8lbl->register_hooks();

	class Mt8_Librize_Book_List {
		
		const TEXT_DOMAIN = 'mt8-librize-book-list';
		
		const API_ENDPOINT = 'http://librize.com/places/%d/place_items.json?limit=%d';
		
		public function __construct() {
			
		}

		public function register_hooks() {
			
			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
			
			add_shortcode( 'librize-book-list', array( &$this, 'librize_book_list' ) );
			
		}
		
		public function plugins_loaded() {
			
			load_plugin_textdomain( self::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ).'/languages' );
			
		}
		
		public function librize_book_list( $atts ) {
			
			$atts = shortcode_atts(
						array(
							'id'         => '',
							'limit'      => 5,
							'wrap'       => 'div',
							'wrap_class' => 'mt8lbl_book_list',
							'no_title'   => false,
							'random'     => false,
							'link'       => true,
							'image_size' => 75,
						), $atts
					);

			$data = $this->get_book_list( $atts );
			if ( ! is_array( $data )  ) {
				return;
			}
			
			return $this->book_list_html( $atts, $data );
			
		}
		
		public function get_book_list( $atts ) {

			$response = wp_remote_get( sprintf( self::API_ENDPOINT, $atts['id'], $atts['limit'] ) );
			
			if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
				return null;
			}

			$json = wp_remote_retrieve_body( $response );
			
			return apply_filters( 'mt8_get_book_list', json_decode( $json ) );
			
		}

		public function book_list_html( $atts, $data ) {

			if ( true == $atts['random'] ) {
				shuffle( $data );
			}

			$start_el = apply_filters( 'mt8_book_list_start_el', '<' . $atts['wrap'] . ' class="' . esc_attr( $atts['wrap_class'] ) . '">' );
			$end_el   = apply_filters( 'mt8_book_list_end_el', '</' . $atts['wrap'] . '>' );

			$book_list = '<ul class="mt8lbl_items">';
			
			$book_items = '';
			foreach ( $data as $book ) {
				
				$book_items .= apply_filters( 'mt8_book_item_html', $this->book_item_html( $atts, $book ) );
				
			}
			$book_list .= $book_items;
			$book_list .= '</ul>';
			
			return $start_el . $book_list . $end_el; 
			
		}
		
		public function book_item_html( $atts, $book ) {
			
			if ( ! $this->check_book_obj( $book ) ) {
				return '';
			}

			$item  = '';
			$item .= '<li class="mt8lbl_item">';

			if ( true == $atts['link'] ) {
				$item .= '<a class="mt8lbl_link" href="' . esc_url( $book->url ) . '" target="_blank">';
			}

			if ( 75 != $atts['image_size'] ) {
				$book->image = preg_replace( '/_SX[0-9]+_/', '_SX' . $atts['image_size'] . '_', $book->image );
			}

			$item .= '<img class="mt8lbl_image" src="' . esc_url( $book->image ) . '" alt="' . esc_attr( $book->title ) . '">';

			if ( false == $atts['no_title'] ) {
				$item .= '<span class="mt8lbl_title">' . esc_html( $book->title ) . '</span>';
			}

			if ( true == $atts['link'] ) {
				$item .= '</a>';
			}

			$item .= '</li>';

			return $item;
			
		}
		
		public function check_book_obj( $book ) {

			$props = array( 'url', 'image', 'title');
			foreach ( $props as $prop ) {
				if ( ! property_exists( $book, $prop ) ) {
					return false;
				}
			}
			return true;
			
		}
		
	}