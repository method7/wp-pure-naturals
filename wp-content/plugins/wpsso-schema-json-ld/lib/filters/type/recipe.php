<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO SCHEMA JSON-LD
 * MARKUP (WPSSO JSON) PREMIUM APPLICATION, YOU AGREE TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE
 * TERMS OF ITS LICENSE AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO SCHEMA JSON-LD MARKUP (WPSSO JSON) PREMIUM
 * APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso-schema-json-ld/license/premium.txt
 * 
 * Copyright 2016-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeRecipe' ) ) {

	class WpssoJsonFiltersTypeRecipe {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_recipe' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_recipe( $json_data, $mod, $mt_og, $page_type_id, $is_main  ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$ret = array();

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_opts = SucomUtil::get_opts_begin( 'schema_recipe_', 
					array_merge( 
						(array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] ), 
						(array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] )	// Returns empty string if no meta found.
					)
				);

			} else {
				$md_opts = array();
			}

			/**
			 * Property:
			 * 	recipeCuisine
			 */
			if ( ! empty( $md_opts[ 'schema_recipe_cuisine' ] ) ) {

				$ret[ 'recipeCuisine' ] = (string) $md_opts[ 'schema_recipe_cuisine' ];
			}

			/**
			 * Property:
			 * 	recipeCategory
			 */
			if ( ! empty( $md_opts[ 'schema_recipe_course' ] ) ) {

				$ret[ 'recipeCategory' ] = (string) $md_opts[ 'schema_recipe_course' ];
			}

			/**
			 * Property:
			 * 	recipeYield
			 */
			if ( ! empty( $md_opts[ 'schema_recipe_yield' ] ) ) {

				$ret[ 'recipeYield' ] = (string) $md_opts[ 'schema_recipe_yield' ];
			}

			/**
			 * Property:
			 * 	cookingMethod
			 */
			if ( ! empty( $md_opts[ 'schema_recipe_cook_method' ] ) ) {

				$ret[ 'cookingMethod' ] = (string) $md_opts[ 'schema_recipe_cook_method' ];
			}

			/**
			 * Property:
			 * 	prepTime
			 * 	cookTime
			 * 	totalTime
			 */
			WpssoSchema::add_data_time_from_assoc( $ret, $md_opts, array(
				'prepTime'  => 'schema_recipe_prep',
				'cookTime'  => 'schema_recipe_cook',
				'totalTime' => 'schema_recipe_total',
			) );

			/**
			 * Property:
			 * 	recipeIngredient (supersedes ingredients)
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^schema_recipe_ingredient_[0-9]+$/', $md_opts ) as $md_key => $value ) {

				$ret[ 'recipeIngredient' ][] = $value;
			}

			/**
			 * Property:
			 * 	recipeInstructions
			 */
			foreach ( SucomUtil::preg_grep_keys( '/^schema_recipe_instruction_[0-9]+$/', $md_opts ) as $md_key => $value ) {

				$ret[ 'recipeInstructions' ][] = $value;
			}

			/**
			 * Property:
			 * 	nutrition as https://schema.org/NutritionInformation
			 */
			if ( ! empty( $md_opts[ 'schema_recipe_nutri_serv' ] ) ) {	// serving size is required

				if ( false !== ( $nutrition = WpssoSchema::get_data_itemprop_from_assoc( $md_opts, array(
					'servingSize'           => 'schema_recipe_nutri_serv',
					'calories'              => 'schema_recipe_nutri_cal',
					'proteinContent'        => 'schema_recipe_nutri_prot',
					'fiberContent'          => 'schema_recipe_nutri_fib',
					'carbohydrateContent'   => 'schema_recipe_nutri_carb',
					'sugarContent'          => 'schema_recipe_nutri_sugar',
					'sodiumContent'         => 'schema_recipe_nutri_sod',
					'fatContent'            => 'schema_recipe_nutri_fat',
					'saturatedFatContent'   => 'schema_recipe_nutri_sat_fat',
					'unsaturatedFatContent' => 'schema_recipe_nutri_unsat_fat',
					'transFatContent'       => 'schema_recipe_nutri_trans_fat',
					'cholesterolContent'    => 'schema_recipe_nutri_chol',
				) ) ) ) {

					self::add_nutrition_measures( $nutrition );

					$ret[ 'nutrition' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/NutritionInformation', $nutrition );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $ret, $is_main );
		}

		private static function add_nutrition_measures( array &$nutrition ) {

			$measures = array(
				'calories'              => 'calories',
				'proteinContent'        => 'grams protein',
				'fiberContent'          => 'grams fiber',
				'carbohydrateContent'   => 'grams carbohydrates',
				'sugarContent'          => 'grams sugar',
				'sodiumContent'         => 'milligrams sodium',
				'fatContent'            => 'grams fat',
				'saturatedFatContent'   => 'grams saturated fat', 
				'unsaturatedFatContent' => 'grams unsaturated fat',
				'transFatContent'       => 'grams trans fat',
				'cholesterolContent'    => 'milligrams cholesterol',
			);

			foreach ( $nutrition as $prop_name => &$value ) {		// Update value by reference.
				if ( isset( $measures[ $prop_name ] ) ) {
					$value .= ' ' . $measures[ $prop_name ];	// Add measure unit.
				}
			}
		}
	}
}
