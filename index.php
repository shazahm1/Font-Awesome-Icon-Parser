<?php

/**
 * Replace both files with the latest version from FontAwesome.
 * @link https://github.com/FortAwesome/Font-Awesome/tree/master/metadata
 *
 * Convert the yml files to array.
 */
$iconsYML      = yaml_parse_file( 'icons.yml' );
$categoriesYML = yaml_parse_file( 'categories.yml' );

//$styles     = [];
//$categories = [];
//$icons      = [];
//$grouped    = [];

///**
// * Restructure the icons array and extract the various styles.
// */
//if ( FALSE !== $iconsYML && is_array( $iconsYML ) ) {
//
//	//echo count( $iconsYML ) . PHP_EOL;
//
//	foreach ( $iconsYML as $id => $icon ) {
//
//		$icons[ $id ] = [
//			'label'  => $icon['label'],
//			'styles' => $icon['styles'],
//			'terms'  => $icon['search']['terms'],
//		];
//
//		if ( isset( $icon['styles'] ) && is_array( $icon['styles'] ) ) {
//
//			foreach ( $icon['styles'] as $style ) {
//
//				//$icons[ $style ][ $id ] = $icon['label'];
//
//				if ( ! in_array( $style, $styles ) ) {
//
//					$styles[] = $style;
//				}
//			}
//		}
//	}
//
//	//var_dump( $icons );
//}

//if ( FALSE !== $categoriesYML && is_array( $categoriesYML ) ) {
//
//	//echo count( $categoriesYML ) . PHP_EOL;
//
//	foreach ( $categoriesYML as $categoryID => $category ) {
//
//		$categories[ $categoryID ] = [
//			'label' => $category['label'],
//			'icons' => $category['icons'],
//		];
//	}
//
//	//var_dump( $categories );
//}

/**
 * Get icons by style.
 *
 * @param string $style brands|regular|solid
 * @param array  $icons The array from yaml_parse_file( 'icons.yml' )
 * @param bool   $includeTerms
 *
 * @return array
 */
function getStyle( string $style, array $icons, bool $includeTerms = FALSE ): array {

	$data = [];

	foreach ( $icons as $iconID => $icon ) {

		if ( in_array( $style, $icon['styles'] ) ) {

			$data[ $iconID ] = [
				'label'  => $icon['label'],
				'styles' => [ $style ],
			];

			if ( $includeTerms ) {

				$data[ $iconID ] = $data[ $iconID ] + [ 'terms' => $icon['search']['terms'] ];
			}
		}
	}

	return $data;
}

/**
 * Get icons by multiple styles.
 *
 * @param array $styles brands|regular|solid
 * @param array $icons  The array from yaml_parse_file( 'icons.yml' )
 * @param bool  $includeTerms
 *
 * @return array
 */
function getStyles( array $styles, array $icons, bool $includeTerms = FALSE ): array {

	$data = [];

	foreach ( $icons as $iconID => $icon ) {

		/**
		 * @link https://stackoverflow.com/a/11040612/5351316
		 */
		if ( ! empty( array_intersect( $styles, $icon['styles'] ) ) ) {

			$data[ $iconID ] = [
				'label'  => $icon['label'],
				'styles' => $icon['styles'],
			];

			if ( $includeTerms ) {

				$data[ $iconID ] = $data[ $iconID ] + [ 'terms' => $icon['search']['terms'] ];
			}
		}
	}

	return $data;
}

/**
 * Categorize icons.
 *
 * @param array $categories The array from yaml_parse_file( 'categories.yml' )
 * @param array $icons      The array from yaml_parse_file( 'icons.yml' )
 * @param bool  $includeTerms
 *
 * @return array
 */
function categorize( array $categories, array $icons, bool $includeTerms = FALSE ): array {

	$data = [];

	foreach ( $categories as $categoryID => $category ) {

		$data[ $categoryID ] = [
			'label' => $category['label'],
			'icons' => [],
		];

		foreach ( $icons as $iconID => $icon ) {

			if ( in_array( $iconID, $category['icons'] ) ) {

				if ( ! $includeTerms ) {

					unset( $icon['terms'] );
				}

				$data[ $categoryID ]['icons'][ $iconID ] = $icon;
			}
		}
	}

	return $data;
}

/**
 * Structure array to be compatible with FontIconPicker.
 *
 * @param array $icons         The array from getStyle(), getStyles() or categorize()
 * @param bool  $isCategorized Whether or not the supplied icons array was categorized by categorize()
 *
 * @return array|stdClass
 */
function structureForFontIconPicker( array $icons, bool $isCategorized = FALSE ) {

	if ( $isCategorized ) {

		$data = new stdClass();

		foreach ( $icons as $categoryID => $category ) {

			$categoryIcons = [];

			foreach ( $category['icons'] as $iconID => $icon ) {

				foreach ( $icon['styles'] as $style ) {

					$id = 'fa' . substr( $style, 0, 1 ) . ' fa-' . $iconID;

					array_push( $categoryIcons, $id );
				}
			}

			$data->{$category['label']} = $categoryIcons;
		}

	} else {

		$data = [];

		foreach ( $icons as $iconID => $icon ) {

			foreach ( $icon['styles'] as $style ) {

				$id = 'fa' . substr( $style, 0, 1 ) . ' fa-' . $iconID;

				$data[] = $id;
			}
		}
	}

	return $data;
}

/**
 * Create an array of search terms compatible with structured reuired by FontIconPicker.
 *
 * @param array $icons         The array from getStyle(), getStyles() or categorize()
 * @param bool  $isCategorized Whether or not the supplied icons array was categorized by categorize()
 *
 * @return array|stdClass
 */
function termsForFontIconPicker( array $icons, bool $isCategorized = FALSE ) {

	if ( $isCategorized ) {

		$data = new stdClass();

		foreach ( $icons as $categoryID => $category ) {

			$terms = [];

			foreach ( $category['icons'] as $iconID => $icon ) {

				foreach ( $icon['styles'] as $style ) {

					$terms[] = isset( $icon['terms'][0] ) ? $icon['terms'][0] : mb_strtolower( $icon['label'] );
				}
			}

			$data->{$category['label']} = $terms;
		}

	} else {

		$data = [];

		foreach ( $icons as $iconID => $icon ) {

			$data[] = isset( $icon['terms'][0] ) ? $icon['terms'][0] : mb_strtolower( $icon['label'] );
		}
	}

	return $data;
}

/**
 * Write data to file.
 *
 * @param string         $file      The file name.
 * @param array|stdClass $data      The data to write to the file.
 * @param bool           $overwrite Whether or not to overwrite the file is it exists.
 */
function writeJSONFile( string $file, $data, bool $overwrite = TRUE ) {

	try {

		if ( file_exists( $file ) && ! $overwrite ) {

			// Get data from existing JSON file.
			$jsondata = file_get_contents( $file );

			// Converts JSON data into an array.
			$arr_data = json_decode( $jsondata, TRUE );

			// Push data to array.
			array_push( $arr_data, $data );

		} else {

			$arr_data = $data;
		}

		// Convert updated array to JSON.
		$jsondata = json_encode( $arr_data, JSON_PRETTY_PRINT );

		// Write JSON data into file.
		if ( file_put_contents( $file, $jsondata ) ) {
			echo 'Data successfully saved!' . PHP_EOL;
		} else {
			echo 'error!' . PHP_EOL;
		}

	}
	catch ( Exception $e ) {
		echo 'Caught exception: ', $e->getMessage(), "\n";
	}
}

$brandIcons = getStyle( 'brands', $iconsYML, TRUE );
//$regularIcons = getStyle( 'regular', $icons, TRUE );
//$solidIcons   = getStyle( 'solid', $icons );
$rsIcons = getStyles( [ 'regular', 'solid' ], $iconsYML, TRUE );

//$regularIconsCategorized = categorize( $categoriesYML, $regularIcons, TRUE );
//$solidIconsCategorized   = categorize( $categoriesYML, $solidIcons, TRUE );
$rsIconsCategorized = categorize( $categoriesYML, $rsIcons, TRUE );

$brandIconsFontIconPicker = structureForFontIconPicker( $brandIcons );
//$regularIconsCategorizedFontIconPicker = structureForFontIconPicker( $regularIconsCategorized, TRUE );
//$solidIconsCategorizedFontIconPicker   = structureForFontIconPicker( $solidIconsCategorized, TRUE );
//$rsIconsFontIconPicker                 = structureForFontIconPicker( $rsIcons );
$rsIconsCategorizedFontIconPicker = structureForFontIconPicker( $rsIconsCategorized, TRUE );

$brandTermsFontIconPicker = termsForFontIconPicker( $brandIcons );
$rsTermsFontIconPicker    = termsForFontIconPicker( $rsIconsCategorized, TRUE );

//var_dump( $brandTermsFontIconPicker );

writeJSONFile( 'fontawesome-brands.json', $brandIconsFontIconPicker );
writeJSONFile( 'fontawesome-rs-categorized.json', $rsIconsCategorizedFontIconPicker );

writeJSONFile( 'fontawesome-brands-terms.json', $brandTermsFontIconPicker );
writeJSONFile( 'fontawesome-rs-categorized-terms.json', $rsTermsFontIconPicker );
