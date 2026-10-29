<?php
/**
 * Plugin deactivation.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS;

final class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
