<?php
/**
 * The same sections WordPress.org shows on a plugin page.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Directory_Tabs {

	public static function render(): void {
		$tabs = array(
			'description'  => 'Description',
			'installation' => 'Installation',
			'faq'          => 'FAQ',
			'screenshots'  => 'Screenshots',
			'changelog'    => 'Changelog',
			'reviews'      => 'Reviews',
			'other_notes'  => 'Other Notes',
		);
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'description';
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'description';
		}

		$meta = self::header();
		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $meta['name'] ) . '</h1>';
		if ( '' !== $meta['short'] ) {
			echo '<p>' . esc_html( $meta['short'] ) . '</p>';
		}
		echo '<p>By <a href="' . esc_url( $meta['homepage'] ) . '" target="_blank" rel="noopener noreferrer">Hundredaire Labs</a></p>';
		echo '<table class="widefat striped" style="max-width:760px;margin:1rem 0"><tbody>';
		foreach ( self::meta_rows( $meta ) as $label => $value ) {
			echo '<tr><th scope="row" style="width:14rem">' . esc_html( $label ) . '</th><td>' . $value . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $label ) {
			$url   = add_query_arg(
				array(
					'page' => 'mbss-stripe-details',
					'tab'  => $id,
				),
				admin_url( 'admin.php' )
			);
			$class = $current === $id ? 'nav-tab nav-tab-active' : 'nav-tab';
			echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
		echo '<div style="max-width:760px;margin-top:1.25rem">';
		if ( 'reviews' === $current ) {
			echo '<p>Reviews appear on the WordPress.org plugin page after the plugin is listed. Questions about the product can go to <a href="' . esc_url( $meta['homepage'] ) . '" target="_blank" rel="noopener noreferrer">Hundredaire Labs</a>.</p>';
		} elseif ( 'screenshots' === $current ) {
			self::screenshots( self::section( 'screenshots' ) );
		} else {
			$names = array(
				'faq'         => 'frequently asked questions',
				'other_notes' => 'other notes',
			);
			echo self::format( self::section( $names[ $current ] ?? $current ) );
		}
		echo '</div></div>';
	}

	/**
	 * @return array{name:string,short:string,homepage:string,version:string,requires:string,tested:string,php:string,license:string,license_uri:string,tags:string,contributors:string}
	 */
	private static function header(): array {
		$path = MBSS_STRIPE_DIR . 'readme.txt';
		$raw  = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
		$raw  = preg_replace( '/^\xEF\xBB\xBF/', '', $raw ) ?? $raw;
		$name = 'Course Deposits for Stripe';
		if ( preg_match( '/^===\s*(.+?)\s*===\s*$/m', $raw, $title ) ) {
			$name = trim( $title[1] );
		}
		$fields = array(
			'contributors' => '',
			'donate link'  => 'https://hundredairelabs.com',
			'tags'         => '',
			'requires at least' => '',
			'tested up to' => '',
			'stable tag'   => MBSS_STRIPE_VERSION,
			'requires php' => '',
			'license'      => '',
			'license uri'  => '',
		);
		foreach ( $fields as $label => $fallback ) {
			if ( preg_match( '/^' . preg_quote( $label, '/' ) . ':\s*(.+)$/mi', $raw, $match ) ) {
				$fields[ $label ] = trim( $match[1] );
			}
		}
		$short = '';
		if ( preg_match( '/^===\s*.+?\s*===\s*(.*?)\s*(?:^==\s)/ms', $raw, $blurb ) ) {
			$short = trim( preg_replace( '/^[A-Za-z ]+:.*$/m', '', $blurb[1] ) ?? '' );
		}
		return array(
			'name'         => $name,
			'short'        => $short,
			'homepage'     => '' !== $fields['donate link'] ? $fields['donate link'] : 'https://hundredairelabs.com',
			'version'      => $fields['stable tag'],
			'requires'     => $fields['requires at least'],
			'tested'       => $fields['tested up to'],
			'php'          => $fields['requires php'],
			'license'      => $fields['license'],
			'license_uri'  => $fields['license uri'],
			'tags'         => $fields['tags'],
			'contributors' => $fields['contributors'],
		);
	}

	/**
	 * @param array{name:string,short:string,homepage:string,version:string,requires:string,tested:string,php:string,license:string,license_uri:string,tags:string,contributors:string} $meta
	 * @return array<string, string>
	 */
	private static function meta_rows( array $meta ): array {
		$license = esc_html( $meta['license'] );
		if ( '' !== $meta['license_uri'] ) {
			$license = '<a href="' . esc_url( $meta['license_uri'] ) . '" target="_blank" rel="noopener noreferrer">' . $license . '</a>';
		}
		return array(
			'Version'          => esc_html( $meta['version'] ),
			'Author'           => '<a href="' . esc_url( $meta['homepage'] ) . '" target="_blank" rel="noopener noreferrer">Hundredaire Labs</a>',
			'Plugin homepage'  => '<a href="' . esc_url( $meta['homepage'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $meta['homepage'] ) . '</a>',
			'Requires WordPress' => esc_html( $meta['requires'] ),
			'Tested up to'     => esc_html( $meta['tested'] ),
			'Requires PHP'     => esc_html( $meta['php'] ),
			'License'          => $license,
			'Tags'             => esc_html( $meta['tags'] ),
			'Contributors'     => esc_html( $meta['contributors'] ),
		);
	}

	private static function section( string $name ): string {
		$path = MBSS_STRIPE_DIR . 'readme.txt';
		$raw  = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
		$raw  = preg_replace( '/^\xEF\xBB\xBF/', '', $raw ) ?? $raw;
		$parts = preg_split( '/^==\s*(.+?)\s*==\s*$/m', $raw, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		$wanted = strtolower( $name );
		for ( $i = 1; $i < count( $parts ); $i += 2 ) {
			if ( strtolower( trim( $parts[ $i ] ) ) === $wanted ) {
				return trim( (string) ( $parts[ $i + 1 ] ?? '' ) );
			}
		}
		return '';
	}

	private static function format( string $text ): string {
		if ( '' === $text ) {
			return '<p>This section is empty.</p>';
		}
		$html      = '';
		$list      = '';
		$list_tag  = 'ul';
		$block     = '';
		$flush_list = static function () use ( &$html, &$list, &$list_tag ): void {
			if ( '' === $list ) {
				return;
			}
			$html .= '<' . $list_tag . '>' . $list . '</' . $list_tag . '>';
			$list  = '';
		};
		$flush_block = static function () use ( &$html, &$block ): void {
			if ( '' === trim( $block ) ) {
				$block = '';
				return;
			}
			$html .= '<p>' . self::inline( trim( $block ) ) . '</p>';
			$block = '';
		};

		foreach ( preg_split( "/\r\n|\n|\r/", $text ) ?: array() as $line ) {
			if ( preg_match( '/^=\s*(.+?)\s*=$/', $line, $heading ) ) {
				$flush_list();
				$flush_block();
				$html .= '<h2>' . self::inline( $heading[1] ) . '</h2>';
				continue;
			}
			if ( preg_match( '/^(\*|-)\s+(.+)$/', $line, $item ) ) {
				$flush_block();
				if ( 'ul' !== $list_tag && '' !== $list ) {
					$flush_list();
				}
				$list_tag = 'ul';
				$list    .= '<li>' . self::inline( $item[2] ) . '</li>';
				continue;
			}
			if ( preg_match( '/^\d+\.\s+(.+)$/', $line, $item ) ) {
				$flush_block();
				if ( 'ol' !== $list_tag && '' !== $list ) {
					$flush_list();
				}
				$list_tag = 'ol';
				$list    .= '<li>' . self::inline( $item[1] ) . '</li>';
				continue;
			}
			if ( '' === trim( $line ) ) {
				$flush_list();
				$flush_block();
				continue;
			}
			$flush_list();
			$block .= ( '' === $block ? '' : ' ' ) . trim( $line );
		}
		$flush_list();
		$flush_block();
		return $html;
	}

	private static function inline( string $text ): string {
		$html = esc_html( $text );
		$html = preg_replace_callback(
			'/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/',
			static function ( array $match ): string {
				$url = esc_url( html_entity_decode( $match[2], ENT_QUOTES ) );
				if ( '' === $url ) {
					return $match[0];
				}
				return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $match[1] . '</a>';
			},
			$html
		) ?? $html;
		$html = preg_replace_callback(
			'/`([^`]+)`/',
			static function ( array $match ): string {
				return '<code>' . $match[1] . '</code>';
			},
			$html
		) ?? $html;
		$html = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html ) ?? $html;
		return preg_replace(
			'#(?<!["\'>])https?://[^\s<)]+#',
			'<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>',
			$html
		) ?? $html;
	}

	private static function screenshots( string $text ): void {
		$lines = preg_split( "/\r\n|\n|\r/", $text ) ?: array();
		$found = false;
		foreach ( $lines as $line ) {
			if ( ! preg_match( '/^(\d+)\.\s+(.+)$/', trim( $line ), $shot ) ) {
				continue;
			}
			$found = true;
			$file  = MBSS_STRIPE_DIR . 'assets/screenshot-' . $shot[1] . '.png';
			echo '<figure style="margin:0 0 1.5rem">';
			if ( is_readable( $file ) ) {
				echo '<img src="' . esc_url( MBSS_STRIPE_URL . 'assets/screenshot-' . $shot[1] . '.png' ) . '" alt="' . esc_attr( $shot[2] ) . '" style="max-width:100%;height:auto;border:1px solid #dcdcde">';
			}
			echo '<figcaption>' . esc_html( $shot[1] . '. ' . $shot[2] ) . '</figcaption>';
			echo '</figure>';
		}
		if ( ! $found ) {
			echo '<p>No screenshots are listed yet.</p>';
		}
	}
}
