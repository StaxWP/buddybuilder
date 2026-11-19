<?php
/**
 * Community style (BuddyBuilder > Settings): the owner's accent color, corners, avatar shape, density and font for
 * every style, without CSS. One option; printed as --bpb-* tokens on .bpb-look with the look components (handle
 * bpb-looks), so it reaches the site, the block editor canvas and the Elementor preview the same way. A choice left on
 * "Style default" prints nothing: each style keeps its own value.
 *
 * The tokens beat every style's own values: .bpb-look.bpb-look (two classes) outranks .bpb-look--<style>, and the
 * designed elements (.bpb-mh, .bpb-gh, ...), which set a few tokens again for use outside a style, get those too.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

defined( 'ABSPATH' ) || die();

/**
 * Community style option and its CSS.
 */
final class CommunityStyle {

	/** Option: [ accent, radius, avatar, density, font ] ('' = the style's own). */
	const OPTION = 'bpb_community_style';

	/** Where the tokens go (see the file comment). */
	const SELECTOR = '.bpb-look.bpb-look';

	/** The designed elements inside a style (they set a few tokens again on themselves, see base.css). */
	const ELEMENTS = '.bpb-look :is(.bpb-mh, .bpb-gh, .bpb-about, .bpb-dh, .bpb-rail)';

	/** Tokens the designed elements set again: printed on them too. */
	const ELEMENT_TOKENS = [ '--bpb-radius-lg', '--bpb-pill', '--bpb-button-radius', '--bpb-avatar-radius', '--bpb-page-gap' ];

	/** Corner sizes: card, large card, button, badge, pill shapes (tabs, counts; '' keeps them pills). */
	const RADIUS = [
		'square'  => [ '0', '0', '0', '0', '0' ],
		'small'   => [ '4px', '6px', '4px', '3px', '6px' ],
		'rounded' => [ '12px', '16px', '10px', '6px', '' ],
		'round'   => [ '20px', '26px', '999px', '999px', '' ],
	];

	/** Avatar corner per shape. */
	const AVATAR = [
		'circle'  => '50%',
		'rounded' => '24%',
		'square'  => '0',
	];

	/** Spacing factor per density (comfortable is every style's own spacing). */
	const DENSITY = [
		'compact'  => '0.75',
		'spacious' => '1.3',
	];

	/** Text colors tried on the accent (buttons, badges): white first, else near black. */
	const LIGHT_TEXT = '#ffffff';
	const DARK_TEXT  = '#111111';

	/**
	 * The choices of each setting, for the Settings card: key => [ value => label ] ('' first: the style's own).
	 *
	 * @return array
	 */
	public static function choices() {
		return [
			'radius'  => [
				''        => __( 'Style default', 'stax-buddy-builder' ),
				'square'  => __( 'Square', 'stax-buddy-builder' ),
				'small'   => __( 'Small', 'stax-buddy-builder' ),
				'rounded' => __( 'Rounded', 'stax-buddy-builder' ),
				'round'   => __( 'Round (pill buttons)', 'stax-buddy-builder' ),
			],
			'avatar'  => [
				''        => __( 'Style default', 'stax-buddy-builder' ),
				'circle'  => __( 'Circle', 'stax-buddy-builder' ),
				'rounded' => __( 'Rounded square', 'stax-buddy-builder' ),
				'square'  => __( 'Square', 'stax-buddy-builder' ),
			],
			'density' => [
				'compact'  => __( 'Compact', 'stax-buddy-builder' ),
				''         => __( 'Comfortable (style default)', 'stax-buddy-builder' ),
				'spacious' => __( 'Spacious', 'stax-buddy-builder' ),
			],
			'font'    => [
				''      => __( 'Style default', 'stax-buddy-builder' ),
				'theme' => __( 'Your theme\'s fonts', 'stax-buddy-builder' ),
			],
		];
	}

	/**
	 * Saved values, every key present ('' = the style's own).
	 *
	 * @return array accent, radius, avatar, density, font
	 */
	public static function get() {
		return self::sanitize( (array) get_option( self::OPTION, [] ) );
	}

	/**
	 * Keep known values only.
	 *
	 * @param array $values Raw values.
	 * @return array
	 */
	public static function sanitize( array $values ) {
		$clean = [ 'accent' => isset( $values['accent'] ) ? (string) sanitize_hex_color( strtolower( trim( (string) $values['accent'] ) ) ) : '' ] + array_fill_keys( array_keys( self::choices() ), '' );

		foreach ( self::choices() as $key => $choices ) {
			$value = isset( $values[ $key ] ) ? (string) $values[ $key ] : '';

			if ( isset( $choices[ $value ] ) ) {
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}

	/**
	 * Save (empty values remove the option, so a reset leaves nothing behind).
	 *
	 * @param array $values Raw values.
	 */
	public static function save( array $values ) {
		$clean = array_filter( self::sanitize( $values ), 'strlen' );

		if ( $clean ) {
			update_option( self::OPTION, $clean );
		} else {
			delete_option( self::OPTION );
		}
	}

	/**
	 * Tokens for the saved (or given) values: name => value. Empty when everything is on "Style default".
	 *
	 * @param array|null $values Values (null: the saved ones).
	 * @return array
	 */
	public static function tokens( $values = null ) {
		$values = null === $values ? self::get() : self::sanitize( $values );
		$tokens = [];

		if ( '' !== $values['accent'] ) {
			$text   = self::text_on( $values['accent'] );
			$tokens = [
				'--bpb-accent'                => $values['accent'],
				'--bpb-style-accent'          => $values['accent'],
				// The second accent (gradients, covers) is a shade of the accent that keeps the same text readable.
				'--bpb-style-accent-2'        => self::mix( $values['accent'], self::LIGHT_TEXT === $text ? '#000000' : '#ffffff', 0.18 ),
				'--bpb-accent-2'              => 'var(--bpb-style-accent-2)',
				'--bpb-color-accent-contrast' => $text,
				// Solid buttons on the accent itself (some styles darken their own accent for contrast).
				'--bpb-button-bg'             => 'var(--bpb-color-accent)',
				'--bpb-button-color'          => $text,
			];

			// Accent text (links, active tabs, badges) on a light page: pulled toward the text color when it is too light.
			$ink = self::ink( $values['accent'], '#ffffff', '#1d2327' );

			if ( '' !== $ink ) {
				$tokens['--bpb-accent-ink'] = $ink;
			}
		}

		if ( '' !== $values['radius'] ) {
			list( $card, $large, $button, $badge, $pill ) = self::RADIUS[ $values['radius'] ];

			$tokens += array_filter(
				[
					'--bpb-radius'        => $card,
					'--bpb-radius-lg'     => $large,
					'--bpb-button-radius' => $button,
					'--bpb-badge-radius'  => $badge,
					'--bpb-pill'          => $pill,
				],
				'strlen'
			);
		}

		if ( '' !== $values['avatar'] ) {
			$tokens['--bpb-avatar-radius'] = self::AVATAR[ $values['avatar'] ];
		}

		if ( '' !== $values['density'] ) {
			$factor  = self::DENSITY[ $values['density'] ];
			$tokens += [
				'--bpb-density'  => $factor,
				'--bpb-page-gap' => 'calc(clamp(16px, 2.5vw, 28px) * ' . $factor . ')',
				'--bpb-gap'      => 'calc(var(--wp--preset--spacing--30, 1rem) * ' . $factor . ')',
			];
		}

		if ( 'theme' === $values['font'] ) {
			// "initial" empties the style's font tokens, so the fallbacks (the theme's fonts) apply.
			$tokens += [
				'--bpb-font-body'    => 'initial',
				'--bpb-font-heading' => 'initial',
			];
		}

		/**
		 * Filters the community style tokens printed on .bpb-look.
		 *
		 * @since 2.0.0
		 *
		 * @param array $tokens Token => value.
		 * @param array $values Saved values (accent, radius, avatar, density, font; '' = the style's own).
		 */
		return (array) apply_filters( 'buddy_builder/community_style/tokens', $tokens, $values );
	}

	/**
	 * Token declarations ("--a:b;--c:d").
	 *
	 * @param array $tokens Token => value.
	 * @return string
	 */
	public static function declarations( array $tokens ) {
		$out = '';

		foreach ( $tokens as $name => $value ) {
			$out .= $name . ':' . $value . ';';
		}

		return $out;
	}

	/**
	 * The CSS printed with the look components ('' when nothing is set).
	 *
	 * @return string
	 */
	public static function css() {
		$values = self::get();
		$tokens = self::tokens( $values );
		$own    = array_intersect_key( $tokens, array_flip( self::ELEMENT_TOKENS ) );
		$css    = ( $tokens ? self::SELECTOR . '{' . self::declarations( $tokens ) . '}' : '' ) . ( $own ? self::ELEMENTS . '{' . self::declarations( $own ) . '}' : '' );

		/**
		 * Filters the community style CSS (BuddyBuilder Pro adds the rules a style needs beyond the tokens).
		 *
		 * @since 2.0.0
		 *
		 * @param string $css    CSS.
		 * @param array  $values Saved values.
		 */
		return (string) apply_filters( 'buddy_builder/community_style/css', $css, $values );
	}

	/**
	 * Readable text color on a background: white when it reaches 4.5:1 (WCAG AA), else whichever reads better.
	 *
	 * @param string $hex Background (#rgb or #rrggbb).
	 * @return string
	 */
	public static function text_on( $hex ) {
		$light = self::contrast( $hex, self::LIGHT_TEXT );

		return $light >= 4.5 || $light >= self::contrast( $hex, self::DARK_TEXT ) ? self::LIGHT_TEXT : self::DARK_TEXT;
	}

	/**
	 * Accent as text on a surface: '' when the accent itself reaches 4.5:1 there, else the accent mixed with the text
	 * color just enough (a CSS color-mix() of the tokens, so it follows the page's own text color).
	 *
	 * @param string $accent  Accent.
	 * @param string $surface Page or card color the text sits on.
	 * @param string $text    The style's text color on that surface.
	 * @return string
	 */
	public static function ink( $accent, $surface, $text ) {
		for ( $share = 0; $share <= 100; $share += 5 ) {
			if ( self::contrast( self::mix( $accent, $text, $share / 100 ), $surface ) >= 4.5 ) {
				return $share ? 'color-mix(in srgb, var(--bpb-color-accent) ' . ( 100 - $share ) . '%, var(--bpb-color-text))' : '';
			}
		}

		return 'var(--bpb-color-text)';
	}

	/**
	 * WCAG contrast ratio of two colors.
	 *
	 * @param string $a Color.
	 * @param string $b Color.
	 * @return float
	 */
	public static function contrast( $a, $b ) {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );

		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}

	/**
	 * Relative luminance (WCAG 2).
	 *
	 * @param string $hex Color.
	 * @return float
	 */
	private static function luminance( $hex ) {
		$weights = [ 0.2126, 0.7152, 0.0722 ];
		$sum     = 0;

		foreach ( self::rgb( $hex ) as $i => $channel ) {
			$c    = $channel / 255;
			$sum += $weights[ $i ] * ( $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 ) );
		}

		return $sum;
	}

	/**
	 * Mix two colors.
	 *
	 * @param string $hex    Color.
	 * @param string $with   Color mixed in.
	 * @param float  $amount Share of $with (0 to 1).
	 * @return string #rrggbb
	 */
	public static function mix( $hex, $with, $amount ) {
		$a = self::rgb( $hex );
		$b = self::rgb( $with );

		return vsprintf(
			'#%02x%02x%02x',
			array_map(
				static function ( $x, $y ) use ( $amount ) {
					return (int) round( $x + ( $y - $x ) * $amount );
				},
				$a,
				$b
			)
		);
	}

	/**
	 * Channels of a hex color.
	 *
	 * @param string $hex #rgb or #rrggbb.
	 * @return int[]
	 */
	private static function rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array_map( 'hexdec', str_split( str_pad( substr( $hex, 0, 6 ), 6, '0' ), 2 ) );
	}
}
