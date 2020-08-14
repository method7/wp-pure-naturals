<?php
/**
 * Extends the free functionality of the XML sitemap class.
 *
 * @since 3.4.0
 */

if ( ! class_exists( 'All_in_One_SEO_Pack_Sitemap' ) ) {
	include_once( AIOSEOP_PLUGIN_DIR . 'modules/aioseop_sitemap.php' );
}

class All_in_One_SEO_Pack_Sitemap_Pro extends All_in_One_SEO_Pack_Sitemap {

		/**
		 * Initiates the class instance.
		 *
		 * @since 3.4.0
		 */
		public function __construct() {
			$this->name           = __( 'XML Sitemap', 'all-in-one-seo-pack' ); // Human-readable name of the plugin.
			$this->prefix         = 'aiosp_sitemap_';                           // Option prefix.
			$this->file           = __FILE__;                                   // The current file.
			$this->extra_sitemaps = array();
			$this->extra_sitemaps = apply_filters( $this->prefix . 'extra', $this->extra_sitemaps );
			parent::__construct();

			$this->hooks();
		}

		/**
		 * Registers our hooks.
		 *
		 * @since 3.6.0
		 *
		 * @return void
		 */
		private function hooks() {
			add_filter( 'admin_init', array( $this, 'register_news_sitemap_notice' ) );
		}

		/**
		 * Gets all content for the sitemap.
		 *
		 * @since	3.4.0	Refactored to improve readability.
		 *
		 * @param   string  $sitemap_type   The type of sitemap that has to be generated.
		 * @param   int     $page_number    The page number of the sitemap index.
		 * @return  array   $sitemap_data   All URLs with their meta info (last modified, priority, frequency).
		 */
		public function get_sitemap_data( $sitemap_type, $page_number = 0 ) {
			$sitemap_data = array();

			switch( $sitemap_type ) {
				case 'rss': {
					$sitemap_data = $this->get_sitemap_without_indexes();
					break;
				}
				case 'news': {
					if ( ! aioseop_is_addon_allowed( 'news_sitemap' ) ) {
						return;
					}
					$sitemap_data = $this->get_news_sitemap_data();
					break;
				}
				case 'root': {
					if( $this->options[ "{$this->prefix}indexes" ] ) {
						$sitemap_data = array_merge( $this->get_sitemap_index_filenames() );
					} else {
						$sitemap_data = $this->get_sitemap_without_indexes();
					}
					break;
				}
				case 'addl': {
					$sitemap_data = $this->get_addl_pages();
					break;
				}
				case 'archive': {
					$sitemap_data = $this->get_date_archive_data();
					break;
				}
				case 'author': {
					$sitemap_data = $this->get_author_archive_data();
					break;
				}
				default: {
					$posttypes = $this->options[ "{$this->prefix}posttypes" ];
					if ( empty( $posttypes ) ) {
						$posttypes = array();
					}

					$taxonomies = $this->options[ "{$this->prefix}taxonomies" ];
					if ( empty( $taxonomies ) ) {
						$taxonomies = array();
					}

					if ( in_array( $sitemap_type, $posttypes ) ) {
						$sitemap_data = $this->get_custom_posts_data( $sitemap_type, 'publish', $page_number );
					}
					else if ( in_array( $sitemap_type, $taxonomies ) ) {
						$sitemap_data = $this->get_terms_data( get_terms( $this->get_tax_args( (array) $sitemap_type, $page_number ) ) );
					}
					else if ( is_array( $this->extra_sitemaps ) && in_array( $sitemap_type, $this->extra_sitemaps ) ) {
						$sitemap_data = apply_filters( $this->prefix . 'custom_' . $sitemap_type, $sitemap_data, $page_number, $this_options );
					}
				}
			}

			/**
			 * Allows users to filter the sitemap data for a given page of a sitemap index.
			 *
			 * @param   array   $sitemap_data       All entries for the given sitemap index.
			 * @param   string  $sitemap_type       The type of sitemap (e.g. "root", "posts", "pages", etc.).
			 * @param   int     $page_number        The page number of the sitemap index.
			 * @param   array   $aioseop_options    The user-defined plugin settings.
			 */
			return apply_filters( "{$this->prefix}data", $sitemap_data, $sitemap_type, $page_number, $this->options );
		}

		/**
		 * Gets the last modified timestamp, priority and frequency for taxonomy terms.
		 *
		 * @since   3.4.0   Check if term has sitemap priority/frequency in termmeta. Renamed function to better reflect purpose.
		 *
		 * @param   array   $terms              The taxonomy terms that need their sitemap meta info to be populated.
		 * @return  array   $populated_terms    The taxonomy terms with their sitemap meta info.
		 */
	public function get_terms_data( $terms ) {
		$populated_terms = array();
		if ( is_array( $terms ) && ! empty( $terms ) ) {

			foreach ( $terms as $term ) {
				$pr_info               = array();
				$pr_info['loc']        = $this->get_term_link( $term, $term->taxonomy );
				$pr_info['lastmod']    = $this->get_tax_term_timestamp( $term );
				$pr_info['priority']   = $this->get_default_priority( 'taxonomies' );
				$pr_info['changefreq'] = $this->get_default_frequency( 'taxonomies' );

				if ( isset( $this->options[ $this->prefix . 'prio_taxonomies' ] ) && 'no' !== $this->options[ $this->prefix . 'prio_taxonomies' ] ) {

					if ( 'sel' !== $this->options[ $this->prefix . 'prio_taxonomies' ] ) {
						$pr_info['priority'] = $this->options[ $this->prefix . 'prio_taxonomies' ];
					} elseif ( 'no' !== $this->options[ $this->prefix . 'prio_taxonomies_' . $term->taxonomy ] ) {
						$pr_info['priority'] = $this->options[ $this->prefix . 'prio_taxonomies_' . $term->taxonomy ];
					}
				}

				if ( isset( $this->options[ $this->prefix . 'freq_taxonomies' ] ) && 'no' !== $this->options[ $this->prefix . 'freq_taxonomies' ] ) {

					if ( 'sel' !== $this->options[ $this->prefix . 'freq_taxonomies' ] ) {
						$pr_info['changefreq'] = $this->options[ $this->prefix . 'freq_taxonomies' ];
					} elseif ( 'no' !== $this->options[ $this->prefix . 'freq_taxonomies_' . $term->taxonomy ] ) {
						$pr_info['changefreq'] = $this->options[ $this->prefix . 'freq_taxonomies_' . $term->taxonomy ];
					}
				}

				$termmeta_prio = get_term_meta( $term->term_id, '_aioseop_sitemap_priority', true );
				$termmeta_freq = get_term_meta( $term->term_id, '_aioseop_sitemap_frequency', true );

				if ( ! empty( $termmeta_prio ) ) {
					$pr_info['priority'] = $termmeta_prio;
				}

				if ( ! empty( $termmeta_freq ) ) {
					$pr_info['changefreq'] = $termmeta_freq;
				}

				$pr_info['image:image'] = $this->get_images_from_term( $term );

				$pr_info['rss'] = array(
					'title'       => $term->name,
					'description' => $term->description,
					'pubDate'     => $this->get_date_for_term( $term ),
				);

				$populated_terms[] = $pr_info;
			}
		}

		return $populated_terms;
	}

		/**
		 * Gets the last modified timestamp, priority and frequency for posts.
		 *
		 * @since   3.4.0   Check if post has sitemap priority/frequency in postmeta. Renamed function to better reflect purpose.
		 *
		 * @param           $posts              The posts that need their sitemap meta info to be populated.
		 * @param   bool    $prio_override
		 * @param   bool    $freq_override
		 * @param   string  $linkfunc
		 * @param   string  $type               Type of entity being fetched viz. author, post etc.
		 * @return  array   $populated_posts    The posts with their sitemap meta info.
		 */
	public function get_posts_data( $posts, $prio_override = false, $freq_override = false, $linkfunc = 'get_permalink', $type = 'post' ) {
		$populated_posts = array();
		$args            = array(
			'prio_override' => $prio_override,
			'freq_override' => $freq_override,
			'linkfunc'      => $linkfunc,
		);

		if ( $prio_override && $freq_override ) {
			$stats = 0;
		} else {
			$stats = $this->get_comment_count_stats( $posts );
		}
		if ( is_array( $posts ) ) {
			foreach ( $posts as $key => $post ) {
				// Determine if we check the post for images.
				$is_single    = true;
				$post->filter = 'sample';
				$timestamp    = null;
				if ( 'get_permalink' === $linkfunc ) {
					$url = $this->get_permalink( $post );
				} else {
					$url       = call_user_func( $linkfunc, $post );
					$is_single = false;
				}

				if ( strpos( $url, '__trashed' ) !== false ) {
					// excluded trashed urls.
					continue;
				}

				$date = $post->post_modified_gmt;
				if ( '0000-00-00 00:00:00' === $date ) {
					$date = $post->post_date_gmt;
				}
				if ( '0000-00-00 00:00:00' !== $date ) {
					$timestamp = $date;
					$date      = date( 'Y-m-d\TH:i:s\Z', mysql2date( 'U', $date ) );
				} else {
					$date = 0;
				}

				if ( $prio_override && $freq_override ) {
					$pr_info = array(
						'lastmod'    => $date,
						'changefreq' => null,
						'priority'   => null,
					);
				} else {
					if ( empty( $post->comment_count ) ) {
						$stat = 0;
					} else {
						$stat = $stats;
					}
					if ( ! empty( $stat ) ) {
						$stat['comment_count'] = $post->comment_count;
					}
					$pr_info = $this->get_prio_calc( $date, $stat );
				}

				if ( $freq_override ) {
					$pr_info['changefreq'] = $freq_override;
				}

				if ( $prio_override ) {
					$pr_info['priority'] = $prio_override;
				}

				if ( isset( $this->options[ $this->prefix . 'prio_post' ] ) && 'no' !== $this->options[ $this->prefix . 'prio_post' ] ) {

					if ( 'sel' !== $this->options[ $this->prefix . 'prio_post' ] ) {
						$pr_info['priority'] = $this->options[ $this->prefix . 'prio_post' ];
					} elseif ( 'no' !== $this->options[ $this->prefix . 'prio_post_' . $post->post_type ] ) {
						$pr_info['priority'] = $this->options[ $this->prefix . 'prio_post_' . $post->post_type ];
					}
				}

				if ( isset( $this->options[ $this->prefix . 'freq_post' ] ) && 'no' !== $this->options[ $this->prefix . 'freq_post' ] ) {

					if ( 'sel' !== $this->options[ $this->prefix . 'freq_post' ] ) {
						$pr_info['changefreq'] = $this->options[ $this->prefix . 'freq_post' ];
					} elseif ( 'no' !== $this->options[ $this->prefix . 'freq_post_' . $post->post_type ] ) {
						$pr_info['changefreq'] = $this->options[ $this->prefix . 'freq_post_' . $post->post_type ];
					}
				}

				$postmeta_prio = get_post_meta( $post->ID, '_aioseop_sitemap_priority', true );
				$postmeta_freq = get_post_meta( $post->ID, '_aioseop_sitemap_frequency', true );

				if ( ! empty( $postmeta_prio ) ) {
					$pr_info['priority'] = $postmeta_prio;
				}

				if ( ! empty( $postmeta_freq ) ) {
					$pr_info['changefreq'] = $postmeta_freq;
				}

				$pr_info = array(
					'loc' => $url,
				) + $pr_info; // Prepend loc to	the	array.
				if ( is_float( $pr_info['priority'] ) ) {
					$pr_info['priority'] = sprintf( '%0.1F', $pr_info['priority'] );
				}

				// add the rss specific data.
				if ( $timestamp ) {
					$title = null;
					switch ( $type ) {
						case 'author':
							$title = get_the_author_meta( 'display_name', $key );
							break;
						default:
							$title = get_the_title( $post );
							break;
					}

					// RSS expects the GMT date.
					$timestamp      = mysql2date( 'U', $post->post_modified_gmt );
					$pr_info['rss'] = array(
						'title'       => $title,
						'description' => get_post_field( 'post_excerpt', $post->ID ),
						'pubDate'     => date( 'r', $timestamp ),
						'timestamp  ' => $timestamp,
						'post_type'   => $post->post_type,
					);
				}

				$pr_info['image:image'] = $is_single ? $this->get_images_from_post( $post ) : null;

				$pr_info = apply_filters( $this->prefix . 'prio_item_filter', $pr_info, $post, $args );
				if ( ! empty( $pr_info ) ) {
					$populated_posts[] = $pr_info;
				}
			}
		}

		return $populated_posts;
	}

		/**
		 * Sets the priority and frequency for the static blogpage.
		 *
		 * @since   3.4.0
		 *
		 * @param   array   $links
		 * @return  array   $links
		 */
	protected function get_prio_freq_static_blogpage( $links ) {
		$blogpage_id = (int) get_option( 'page_for_posts' );
		$permalink   = get_permalink( $blogpage_id );

		if ( 0 === $blogpage_id || 'page' !== get_option( 'show_on_front' ) ) {
			return $links;
		}

		$blogpage_index = array_search( $permalink, array_column( $links, 'loc' ) ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_columnFound

		if ( isset( $this->options[ $this->prefix . 'prio_post' ] ) && 'no' !== $this->options[ $this->prefix . 'prio_post' ] ) {

			if ( 'sel' !== $this->options[ $this->prefix . 'prio_post' ] ) {
				$links[ $blogpage_index ]['priority'] = $this->options[ $this->prefix . 'prio_post' ];
			} elseif ( 'no' !== $this->options[ $this->prefix . 'prio_post_' . 'page' ] ) {
				$links[ $blogpage_index ]['priority'] = $this->options[ $this->prefix . 'prio_post_' . 'page' ];
			}
		}

		if ( isset( $this->options[ $this->prefix . 'freq_post' ] ) && 'no' !== $this->options[ $this->prefix . 'freq_post' ] ) {

			if ( 'sel' !== $this->options[ $this->prefix . 'freq_post' ] ) {
				$links[ $blogpage_index ]['changefreq'] = $this->options[ $this->prefix . 'freq_post' ];
			} elseif ( 'no' !== $this->options[ $this->prefix . 'freq_post_' . 'page' ] ) {
				$links[ $blogpage_index ]['changefreq'] = $this->options[ $this->prefix . 'freq_post_' . 'page' ];
			}
		}

		$postmeta_prio = get_post_meta( $blogpage_id, '_aioseop_sitemap_priority', true );
		$postmeta_freq = get_post_meta( $blogpage_id, '_aioseop_sitemap_frequency', true );

		if ( ! empty( $postmeta_prio ) ) {
			$links[ $blogpage_index ]['priority'] = $postmeta_prio;
		}

		if ( ! empty( $postmeta_freq ) ) {
			$links[ $blogpage_index ]['changefreq'] = $postmeta_freq;
		}
		return $links;
	}

		/**
		 * Returns the content for the News Sitemap.
		 *
		 * @since	3.4.0
		 *
		 * @return	array	$entries
		 */
		private function get_news_sitemap_data() {
			global $aioseop_options;

			if ( ! apply_filters( 'aioseo_news_sitemap_enabled', true ) ) {
				return array();
			}

			// Use 'post' as default if options have not been set yet.
			if ( ! isset( $aioseop_options['modules']['aiosp_sitemap_options'] ) || ! isset( $this->options['aiosp_sitemap_posttypes_news'] ) ) {
				$this->options['aiosp_sitemap_posttypes_news'] = array( 'post' );
			}

			$post_types = apply_filters( 'aioseo_news_sitemap_post_types', $this->options['aiosp_sitemap_posttypes_news'] );
			if ( ! $post_types || ! is_array( $post_types ) ) {
				return array();
			}

			$args = array(
				'numberposts'   => 50000,
				'orderby'       => 'date',
				'order'         => 'DESC',
				'date_query'    => array(
					array(
						'after' => '48 hours ago'
					),
				),
				'no_found_rows' => true,
				'cache_results' => false,
				'meta_query'    => array(
				'relation'      => 'AND',
					array(
						'relation' => 'OR',
						array(
						'key'     => '_aioseop_sitemap_exclude',
						'value'   => 'on',
						'compare' => '!='
						),
						array(
						'key'     => '_aioseop_sitemap_exclude',
						'compare' => 'NOT EXISTS'
						),
					),
				),
			);

			$totalPosts = array();
			foreach ( $post_types as $post_type ) {
				$args['post_type'] = $post_type;
				// Don't include posts that are noindexed.
				if (
					isset( $aioseop_options['aiosp_cpostnoindex'] ) &&
					$aioseop_options['aiosp_cpostnoindex'] &&
					in_array( $post_type, $aioseop_options['aiosp_cpostnoindex'], true )
				) {
					array_push( $args['meta_query'],
						array(
							'key'     => '_aioseop_noindex',
							'value'   => 'off',
							'compare' => '=',
						)
					);
				} else {
					$extraArgs = array(
						'relation' => 'OR',
						array(
							'key'     => '_aioseop_noindex',
							'value'   => 'on',
							'compare' => '!=',
						),
						array(
							'key'     => '_aioseop_noindex',
							'compare' => 'NOT EXISTS',
						),
					);

					array_push( $args['meta_query'], $extraArgs );
				}

				$posts = get_posts( $args );
				if ( $posts ) {
					$totalPosts = array_merge( $totalPosts, $posts );
				}
			}

			if ( ! $totalPosts ) {
				return array();
			}

			$entries = array();
			foreach ( $totalPosts as $post ) {
				$entry = array(
					'location'         => get_permalink( $post->ID ),
					'publication'      => array(
						'language' => $this->get_publication_language(),
					),
					'publication_date' => date( 'c', mysql2date( 'U', $post->post_date ) ),
					'title'            => $post->post_title,
				);

				array_push( $entries, $entry );
			}

			return $entries;
		}

		/**
		 * Returns the language code for the site in the ISO 639-1 format.
		 *
		 * @since	3.4.0
		 *
		 * @return	string
		 */
		private function get_publication_language() {
			$locale = get_locale();

			if ( strlen( $locale ) < 2 ) {
				return $locale = 'en';
			}

			// These are two exceptions as stated on https://support.google.com/news/publisher-center/answer/9606710.
			if ( 'zh_CN' === $locale ) {
				return 'zh-cn';
			}

			if ( 'zh_TW' === $locale ) {
				return 'zh-tw';
			}

			return substr( $locale, 0, 2 );
		}

		/**
		 * Output Sitemap
		 *
		 * Output the XML for a sitemap.
		 *
		 * @since 	?
		 * @since	3.4.0	Added support for News Sitemap.
		 *
		 * @param        $urls
		 * @param string $sitemap_type The type of sitemap viz. root, rss, rss_latest etc.. For static sitemaps, this would be empty.
		 * @param string $comment
		 * @return null
		 */
		protected function output_sitemap( $urls, $sitemap_type, $comment = '' ) {
			if ( 'rss' === $sitemap_type ) {
				$this->output_rss( $urls, $sitemap_type, $comment );
				return;
			}

			else if( 'news' === $sitemap_type ) {
				$this->output_news_sitemap( $urls, $comment );
				return;
			}

			$max_items = 50000;
			if ( ! is_array( $urls ) ) {
				return null;
			}
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n\r\n";
			// TODO Add esc_* function.
			echo '<!-- ' . sprintf( $this->comment_string, $comment, AIOSEOP_VERSION, date( 'D, d M Y H:i:s e' ) ) . " -->\r\n";
			$plugin_path  = $this->plugin_path['url'];
			$plugin_url   = wp_parse_url( $plugin_path );
			$current_host = $_SERVER['HTTP_HOST'];
			if ( empty( $current_host ) ) {
				$current_host = $_SERVER['SERVER_NAME'];
			}

			if ( ! empty( $current_host ) && ( $current_host !== $plugin_url['host'] ) ) {
				$plugin_url['host'] = $current_host;
			}

			// Code `unset( $plugin_url['scheme'] )`.
			$plugin_path = $this->unparse_url( $plugin_url );

			// Using the filter you need the full path to the custom xsl file.
			$xsl_url = $this->get_sitemap_xsl();

			$xml_header = '<?xml-stylesheet type="text/xsl" href="' . $xsl_url . '"?>' . "\r\n" . '<urlset ';
			$namespaces = apply_filters(
				$this->prefix . 'xml_namespace',
				array(
					'xmlns'       => 'http://www.sitemaps.org/schemas/sitemap/0.9',
					'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
				)
			);
			if ( ! empty( $namespaces ) ) {
				$ns = array();
				foreach ( $namespaces as $k => $v ) {
					$ns[] = esc_attr( $k ) . '="' . esc_url( $v, array( 'http', 'https' ) ) . '"';
				}
				$xml_header .= join( "\r\n\t", $ns );
			}
			$xml_header .= '>' . "\r\n";
			// TODO Add esc_* function.
			echo $xml_header;
			$count = 0;
			foreach ( $urls as $url ) {
				echo "\t<url>\r\n";
				if ( is_array( $url ) ) {
					if ( isset( $url['rss'] ) ) {
						unset( $url['rss'] );
					}
					foreach ( $url as $k => $v ) {
						if ( ! empty( $v ) ) {
							$v = aiosp_common::esc_xml( $k, $v );
							if ( is_array( $v ) ) {
								$buf = "\t\t\t<$k>\r\n";
								foreach ( $v as $ext => $attr ) {
									if ( is_array( $attr ) ) {
										$buf = '';
										// TODO Add esc_* function.
										echo "\t\t<$k>\r\n";
										foreach ( $attr as $a => $nested ) {
											if ( is_array( $nested ) ) {
												// TODO Add esc_* function.
												echo "\t\t\t<$a>\r\n";
												foreach ( $nested as $next => $nattr ) {
													$value = aiosp_common::esc_xml( $next, $nattr );
													// TODO Add esc_* function.
													echo "\t\t\t\t<$next>$value</$next>\r\n";
												}
												// TODO Add esc_* function.
												echo "\t\t\t</$a>\r\n";
											} else {
												$value = aiosp_common::esc_xml( $a, $nested );
												// TODO Add esc_* function.
												echo "\t\t\t<$a>$value</$a>\r\n";
											}
										}
										// TODO Add esc_* function.
										echo "\t\t</$k>\r\n";
									} else {
										$value = aiosp_common::esc_xml( $ext, $attr );
										$buf  .= "\t\t\t<$ext>$value</$ext>\r\n";
									}
								}
								if ( ! empty( $buf ) ) {
									// TODO Add esc_* function.
									echo $buf . "\t\t</$k>\r\n";
								}
							} else {
								$value = aiosp_common::esc_xml( $k, $v );
								// TODO Add esc_* function.
								echo "\t\t<$k>$value</$k>\r\n";
							}
						}
					}
				} else {
					$value = aiosp_common::esc_xml( 'loc', $url );
					// TODO Add esc_* function.
					echo "\t\t<loc>$value</loc>\r\n";
				}
				echo "\t</url>\r\n";
				if ( $count >= $max_items ) {
					break;
				}
			}
			echo '</urlset>';
		}

		/**
		 * Outputs the Google News sitemap.
		 *
		 * @since	3.4.0
		 *
		 * @param	array	$urls
		 * @param	string	$comment	Whether the sitemap is a static one or is dynamically generated.
		 */
		private function output_news_sitemap( $urls, $comment ) {
			if ( ! aioseop_is_addon_allowed( 'news_sitemap' ) ) {
				exit();
			}

			if ( isset( $this->options['aiosp_sitemap_publication_name'] ) ) {
				$publication_name = $this->options['aiosp_sitemap_publication_name'] ? $this->options['aiosp_sitemap_publication_name'] : get_bloginfo( 'name' );
			} else {
				$publication_name = get_bloginfo( 'name' );
			}

			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n\r\n";
			echo '<!-- ' . sprintf( $this->comment_string, $comment, AIOSEOP_VERSION, date( 'D, d M Y H:i:s e' ) ) . " -->\r\n";

			$site_url = site_url( '/' );
			echo "<?xml-stylesheet type=\"text/xsl\" href=\"${site_url}news-sitemap.xsl\"?>" . "\r\n";

			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\r\n";

			foreach ( $urls as $url ) {

			echo
				"\t" . '<url>' . "\r\n" .
				"\t\t". '<loc>' . aiosp_common::esc_xml( 'loc', $url['location'] ) . '</loc>' . "\r\n" .
				"\t\t". '<news:news>' . "\r\n" .
				"\t\t\t". '<news:publication>' . "\r\n" .
				"\t\t\t\t". '<news:name>' . aiosp_common::esc_xml( 'news:name', $publication_name ) . '</news:name>' . "\r\n" .
				"\t\t\t\t". '<news:language>' . aiosp_common::esc_xml( 'news:language', $url['publication']['language'] ) . '</news:language>' . "\r\n" .
				"\t\t\t". '</news:publication>' . "\r\n" .
				"\t\t\t". '<news:publication_date>' . aiosp_common::esc_xml( 'news:language', $url['publication_date'] ) . '</news:publication_date>' . "\r\n" .
				"\t\t\t". '<news:title>' . aiosp_common::esc_xml( 'news:title', $url['title'] ) . '</news:title>' . "\r\n" .
				"\t\t". '</news:news>' . "\r\n" .
				"\t" . '</url>' . "\r\n";
			}

			echo '</urlset>';
		}

		/**
		 * Returns a sitemap's XSL styling sheet when requested.
		 *
		 * @since	?
		 * @since	3.4.0	Added stylesheet for News Sitemap.
		 */
		public function make_dynamic_xsl() {
			if ( ! preg_match( '#.*sitemap.xsl#i', $_SERVER['REQUEST_URI'] ) ) {
				return;
			}

			$blog_charset = get_option( 'blog_charset' );
			header( "Content-Type: text/xml; charset=$blog_charset", true );

			if ( aioseop_is_addon_allowed( 'news_sitemap' ) && preg_match( '#.*news-sitemap.xsl#i', $_SERVER['REQUEST_URI'] ) ) {
				include_once AIOSEOP_PLUGIN_DIR . '/inc/news-sitemap-xsl.php';
			}

			else if ( preg_match( '#.*sitemap.xsl#i', $_SERVER['REQUEST_URI'] ) ) {
				include_once AIOSEOP_PLUGIN_DIR . '/inc/sitemap-xsl.php';
			}

			exit();
		}

		/**
		 * Shows a notice if both the Google News Publication Name and Site Title aren't set.
		 *
		 * At least one of these values is needed for the Google News sitemap to be valid.
		 *
		 * @since 3.6.0
		 *
		 * @return void
		 */
		public function register_news_sitemap_notice() {
			global $aioseop_notices;
			$notice_slug = 'news_sitemap';

			// Get Google News Publication Name and Site Title.
			$publication_name = '';
			if ( isset( $this->options['aiosp_sitemap_publication_name'] ) ) {
				$publication_name = $this->options['aiosp_sitemap_publication_name'];
			}
			$blog_name = get_bloginfo( 'name' );

			// If both aren't set, register notice, otherwise deregister it.
			if ( ! empty( $publication_name ) || ! empty( $blog_name ) ) {
				if ( isset( $aioseop_notices->active_notices[ $notice_slug ] ) ) {
					$aioseop_notices->remove_notice( $notice_slug );
				}
				return;
			}

			$aioseop_notices->activate_notice( $notice_slug );
		}
}
