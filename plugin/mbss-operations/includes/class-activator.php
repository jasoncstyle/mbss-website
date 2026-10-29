<?php
/**
 * Plugin activation.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS;

use MBSS\Database\Schema;

final class Activator {

	public static function activate(): void {
		Schema::install();
		flush_rewrite_rules();
	}
}
