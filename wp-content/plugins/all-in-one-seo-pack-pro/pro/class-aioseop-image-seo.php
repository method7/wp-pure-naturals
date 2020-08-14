<?php

/**
 * Adds features for image SEO.
 *
 * @since   3.4.0
 * @package All-in-One-SEO-Pack
 */
if ( ! class_exists( 'All_in_One_SEO_Pack_Image_SEO' ) ) {

	/**
	 * Allows users to optimze their images' title tags and alt tags for SEO.
	 *
	 * @since   3.4.0
	 **/
	class All_in_One_SEO_Pack_Image_Seo extends All_in_One_SEO_Pack_Module {

		/**
		 * Initiates the class.
		 *
		 * @since   3.4.0
		 *
		 * @return  void
		 */
		public function __construct() {

			global $aioseop_options;
			if ( ! aioseop_is_addon_allowed( 'image_seo' ) ) {
				return;
			}

			$this->name   = __( 'Image SEO', 'all-in-one-seo-pack' );
			$this->prefix = 'aiosp_image_seo_';
			$this->file   = __FILE__;

			$this->set_default_settings();
			$this->set_metabox_locations();
			$this->update_options();

			$this->add_hooks();

			parent::__construct();
		}

		/**
		 * Sets the default settings for the module.
		 *
		 * @since   3.4.0
		 *
		 * @return  void
		 */
		private function set_default_settings() {
			$this->default_options = array(
				'title_format'     => array(
					'name'     => __( 'Title Attribute Format', 'all-in-one-seo-pack' ),
					'default'  => '%image_title% | %site_title%',
					'type'     => 'text',
					'sanitize' => 'text',
				),
				'title_strip_punc' => array(
					'name' => __( 'Strip Punctuation For Title Attributes', 'all-in-one-seo-pack' ),
					'type' => 'checkbox',
				),
				'alt_format'       => array(
					'name'     => __( 'Alt Tag Attribute Format', 'all-in-one-seo-pack' ),
					'default'  => '%alt_tag%',
					'type'     => 'text',
					'sanitize' => 'text',
				),
				'alt_strip_punc'   => array(
					'name' => __( 'Strip Punctuation For Alt Tag Attributes', 'all-in-one-seo-pack' ),
					'type' => 'checkbox',
				),
			);
		}

		/**
		 * Assigns settings to their respective metabox.
		 *
		 * @since   3.4.0
		 *
		 * @return  void
		 */
		private function set_metabox_locations() {
			$this->locations = array(
				'image_seo' => array(
					'name'    => $this->name,
					'prefix'  => 'aiosp_',
					'type'    => 'settings',
					'options' => array(
						'title_format',
						'title_strip_punc',
						'alt_format',
						'alt_strip_punc',
					),
				),
			);

			$this->layout = array(
				'default' => array(
					'name'      => __( 'Title & Alt Tag Attribute Settings', 'all-in-one-seo-pack' ),
					'help_link' => 'https://semperplugins.com/documentation/image-seo-module/',
					'options'   => array(),
				),
			);
		}

		/**
		 * Adds all hook callbacks that allow us to add our image SEO functionality.
		 *
		 * @since   3.4.0
		 *
		 * @return  void
		 */
		private function add_hooks() {
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'filter_image_attributes' ), 10, 2 );
			add_filter( 'the_content', array( $this, 'filter_the_content' ) );
			add_filter( 'woocommerce_short_description', array( $this, 'filter_the_content' ) );

			/**
			 * Allows users to disable our image attribute columns in the Media Library.
			 *
			 * @since   3.4.0
			 *
			 * @param   bool        Whether or not the image attribute columns should be added. Defaults to true.
			 */
			if ( apply_filters( 'aioseop_image_attribute_columns', true ) ) {
				add_filter( 'manage_media_columns', array( $this, 'filter_manage_media_columns' ) );
				add_action( 'manage_media_custom_column', 'render_seo_column', 10, 2 );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}
		}

		/**
		 * Registers all scripts that allow us to add our image SEO functionality.
		 *
		 * @since   3.4.0
		 *
		 * @return  void
		 */
		public function admin_enqueue_scripts( $hook_suffix ) {
			$current_screen = get_current_screen()->id;

			if ( 'upload' === $current_screen ) {
				wp_enqueue_script( 'aioseop-media-columns', AIOSEOP_PLUGIN_URL . 'pro/js/admin/aioseop-media-columns.js', array( 'jquery' ), AIOSEOP_VERSION, true );

				wp_localize_script(
					'aioseop-media-columns',
					'aioseopMediaColumnsData',
					array(
						'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
						'nonce'         => wp_create_nonce( 'aioseop_ajax_post_meta' ),
						'i18n'          => array(
							'noTitle'       => __( '(no title)', 'all-in-one-seo-pack' ),
							'image_title'   => __( 'Here you can edit the title of your image. Your image title is also used as your image title attribute.', 'all-in-one-seo-pack' ),
							'image_alt_tag' => __( 'Here you can edit your image alt tag attribute.', 'all-in-one-seo-pack' ),
						),
					)
				);

				if ( ! wp_script_is( 'aioseop-quickedit', 'enqueued' ) ) {
					wp_enqueue_script( 'aioseop-quickedit', AIOSEOP_PLUGIN_URL . 'js/admin/aioseop-quickedit.js', array( 'jquery' ), AIOSEOP_VERSION );
					wp_localize_script(
						'aioseop-quickedit',
						'aioseopadmin',
						array(
							'requestUrl' => WP_ADMIN_URL . '/admin-ajax.php',
							'imgUrl'     => AIOSEOP_PLUGIN_IMAGES_URL,
							'i18n'       => array(
								'save'   => __( 'Save', 'all-in-one-seo-pack' ),
								'cancel' => __( 'Cancel', 'all-in-one-seo-pack' ),
								'wait'   => __( 'Please wait...', 'all-in-one-seo-pack' ),
								'noValue' => __( 'No value', 'all-in-one-seo-pack' ),
							),
						)
					);
				}
			} else if ( 'all-in-one-seo_page_aiosp_image_seo' === $current_screen ) {
				parent::admin_enqueue_scripts( $hook_suffix );
			}
		}

		/**
		 * Filters an image's attributes when it is loaded.
		 *
		 * Acts as a callback for the "wp_get_attachment_image_attributes" filter hook.
		 *
		 * @since   3.4.0
		 *
		 * @param   array   $attributes     Attributes for the image markup.
		 * @param   object  $attachment     The attachment page object.
		 *
		 * @return  array   $attr           The filtered image attributes.
		 */
		public function filter_image_attributes( $attributes, $attachment ) {
			if ( is_admin() || ! is_singular() ) {
				return $attributes;
			}

			/**
			 * Allows users to filter the title attribute of an image.
			 *
			 * @since   3.4.0
			 *
			 * @param   string      $title      The value of the title attribute.
			 * @param   int         $image_id   The ID of the image.
			 */
			$attributes['title'] = apply_filters( 'aioseop_image_seo_title', $this->get_attribute( 'title', $attachment->ID ), $attachment->ID );

			/**
			 * Allows users to filter the alt tag attribute of an image.
			 *
			 * @since   3.4.0
			 *
			 * @param   string      $alt_tag    The value of the title attribute.
			 * @param   int         $image_id   The ID of the image.
			 */
			$attributes['alt'] = apply_filters( 'aioseop_image_seo_alt_tag', $this->get_attribute( 'alt', $attachment->ID ), $attachment->ID );

			return $attributes;
		}

		/**
		 * Filters the content of a post.
		 *
		 * Acts as a callback for the "the_content" filter hook.
		 *
		 * @since   3.4.0
		 *
		 * @param   string  $content    The content of the post.
		 * @return  string  $content    The modified content of the post.
		 */
		public function filter_the_content( $content ) {
			if ( is_admin() || ! is_singular() ) {
				return $content;
			}

			return preg_replace_callback( '/<img[^>]+/', array( $this, 'filter_tags_embedded_images' ), $content, 20 );
		}

		/**
		 * Filters the title and alt tag attribute of embedded images.
		 *
		 * Adds these attributes if they aren't present yet.
		 *
		 * @since   3.4.0
		 *
		 * @param   array   $images     The image tag that has to be filtered.
		 *
		 * @return  string              The filtered image tag.
		 */
		public function filter_tags_embedded_images( $images ) {
			if ( is_admin() ) {
				return $images[0];
			}

			$attributes = preg_split( '/(\w+=)/', $images[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

			if ( ! in_array( 'class=', $attributes, true ) ) {
				return $images[0];
			}

			$class_index = array_search( 'class=', $attributes, true );

			preg_match( '#\d+#', $attributes[ $class_index + 1 ], $matches );

			if ( empty( $matches ) ) {
				return $images[0];
			}

			$image_id = intval( $matches[0] );

			/**
			 * Allows users to filter the title attribute of an image.
			 *
			 * @since   3.4.0
			 *
			 * @param   string      $title      The value of the title attribute.
			 * @param   int         $image_id   The ID of the image.
			 */
			$title   = apply_filters( 'aioseop_image_seo_title', $this->get_attribute( 'title', $image_id ), $image_id );

			/**
			 * Allows users to filter the alt tag attribute of an image.
			 *
			 * @since   3.4.0
			 *
			 * @param   string      $alt_tag    The value of the title attribute.
			 * @param   int         $image_id   The ID of the image.
			 */
			$alt_tag = apply_filters( 'aioseop_image_seo_alt_tag', $this->get_attribute( 'alt', $image_id ), $image_id );

			$title   = sprintf( '"%s"', $title );
			$alt_tag = sprintf( '"%s"', $alt_tag );

			if ( in_array( 'title=', $attributes, true ) ) {
				$index                    = array_search( 'title=', $attributes );
				$attributes[ $index + 1 ] = $title;
			} else {
				array_push( $attributes, 'title=', $title );
			}

			if ( in_array( 'alt=', $attributes, true ) ) {
				$index                    = array_search( 'alt=', $attributes );
				$attributes[ $index + 1 ] = $alt_tag;
			} else {
				array_push( $attributes, 'alt=', $alt_tag );
			}

			return implode( '', $attributes ) . ' /';
		}

		/**
		 * Filters the list of of media columns.
		 *
		 * Acts as a callback for the "manage_media_columns" filter hook.
		 *
		 * @since   3.4.0
		 *
		 * @param   string[]    $post_columns   List of columns in the Media table.
		 * @return  string[]    $post_columns   Filtered list of columns in the Media table.
		 */
		public function filter_manage_media_columns( $post_columns ) {
			$column_headers = array(
				'image_title'           => __( 'Image Title', 'all-in-one-seo-pack' ),
				'image_alt_tag'         => __( 'Image Alt Tag', 'all-in-one-seo-pack' ),
			);

			foreach ( $column_headers as $column_name => $column_header ) {
				$post_columns[ $column_name ] = sprintf(
					'%s %s',
					$column_header,
					'<span class="dashicons dashicons-editor-help aioseop-media-lib-dashicon aioseop-media-lib-tooltip"><span class="aioseop-media-lib-tooltip-text"></span></span>'
				);
			}

			return $post_columns;
		}

		/**
		 * Returns an image attribute after its format has been processed.
		 *
		 * @since   3.4.0
		 *
		 * @param   string      $attribute_name     The name of the attribute ("title" or "alt").
		 * @param   int         $image_id           The ID of the attachment page.
		 *
		 * @return  string      $attribute          The value of the attribute after it has been filtered.
		 */
		private function get_attribute( $attribute_name, $image_id ) {
			// It's important to pass on $attribute_name as $column_name so that the attribute is still returned if the format is not set or blank.
			$attribute = $this->replace_format_macros( $this->options[ "aiosp_image_seo_${attribute_name}_format" ], $image_id, $attribute_name );

			if ( 'on' === $this->options[ "aiosp_image_seo_${attribute_name}_strip_punc" ] ) {
				$attribute = $this->strip_punctuation( $attribute );
			}

			return $attribute;
		}

		/**
		 * Replaces all macros in the format with their respective content.
		 *
		 * @since   3.4.0
		 *
		 * @param   string      $format             The format that needs to be processed.
		 * @param   int         $image_id           The ID of the attachment page.
		 * @param   string      $attribute_name     The name of the image attribute or preview column in the Media Library.
		 *
		 * @return  string      $format             The processed title format.
		 */
		private function replace_format_macros( $format, $image_id, $attribute_name = '' ) {
			$post = $image_title = $category = $category_title = $post_seo_title = $post_seo_description
				= $tax_product_cat = $tax_product_tag = $author = $post_date = $post_year = $post_month = '';

			$post_id = get_the_ID();
			if ( $post_id ) {
				$post                 = get_post( $post_id );
				$category             = get_the_category( $post_id );
				$post_seo_title       = get_post_meta( $post_id, '_aioseop_title', true );
				$post_seo_description = get_post_meta( $post_id, '_aioseop_description', true );
			}

			if ( $post ) {
				$author     = get_userdata( $post->post_author );
				$post_date  = aioseop_formatted_date( $post->post_date );
				$post_year  = mysql2date( 'Y', $post->post_date );
				$post_month = mysql2date( 'M', $post->post_date );
				$taxonomies   = get_object_taxonomies( $post, 'names' );
			}

			if ( $category ) {
				$category_title = $category[0]->name;
			}

			if ( aioseop_is_woocommerce_active() && 'product' === get_post_type() ) {
				$product_cats = get_the_terms( $post_id, 'product_cat' );
				if ( ! empty( $product_cats ) ) {
					$tax_product_cat = $product_cats[0]->name;
				}

				$product_tags = get_the_terms( $post_id, 'product_tag' );
				if ( ! empty( $product_tags ) ) {
					$tax_product_tag = $product_tags[0]->name;
				}
			}

			$attachment = get_post( $image_id );
			if ( $attachment ) {
				$image_title = $attachment->post_title;
			}

			$site_title            = get_bloginfo( 'name' );
			$site_description      = get_bloginfo( 'description' );
			$image_seo_title       = get_post_meta( $image_id, '_aioseop_title', true );
			$image_seo_description = get_post_meta( $image_id, '_aioseop_description', true );
			$alt_tag               = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$current_date          = aioseop_formatted_date();
			$current_year          = date( 'Y' );
			$current_month         = date( 'M' );
			$current_month_i18n    = date_i18n( 'M' );

			$macros = array(
				'%image_title%'           => $image_title,
				'%site_title%'            => $site_title,
				'%blog_title%'            => $site_title,
				'%site_description%'      => $site_description,
				'%image_seo_title%'       => $image_seo_title,
				'%image_seo_description%' => $image_seo_description,
				'%post_seo_title%'        => $post_seo_title,
				'%post_seo_description%'  => $post_seo_description,
				'%post_title%'            => $post->post_title,
				'%category_title%'        => $category_title,
				'%tax_product_cat%'       => $tax_product_cat,
				'%tax_product_tag%'       => $tax_product_tag,
				'%alt_tag%'               => $alt_tag,
				'%page_author_login%'     => $author->user_login,
				'%page_author_nicename%'  => $author->user_nicename,
				'%page_author_firstname%' => $author->first_name,
				'%page_author_lastname%'  => $author->last_name,
				'%current_date%'          => $current_date,
				'%current_year%'          => $current_year,
				'%current_month%'         => $current_month,
				'%current_month_i18n%'    => $current_month_i18n,
				'%post_date%'             => $post_date,
				'%post_year%'             => $post_year,
				'%post_month%'            => $post_month,
			);

			foreach ( $macros as $macro => $value ) {
				$format = str_replace( $macro, trim( $value ), $format );
			}

			return $format;
		}

		/**
		 * Strips punctuation from a string.
		 *
		 * @since   3.4.0
		 *
		 * @param   string  $string     The string that needs to be stripped of its punctuation.
		 * @return  string  $string     The string without punctuation.
		 */
		public function strip_punctuation( $string ) {
			return preg_replace( '#\p{P}#u', '', $string );
		}
	}
}
