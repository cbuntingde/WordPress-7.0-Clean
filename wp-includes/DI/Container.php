<?php
/**
 * Dependency Injection Container.
 *
 * @package WordPress
 * @subpackage DI
 */

namespace WordPress\DI;

use Closure;
use InvalidArgumentException;

class Container {
	private static ?Container $instance = null;
	private array $bindings = [];
	private array $instances = [];

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function reset_instance(): void {
		self::$instance = null;
	}

	public function bind( string $abstract, callable $concrete ): void {
		$this->bindings[ $abstract ] = $concrete;
	}

	public function singleton( string $abstract, $instance ): void {
		$this->instances[ $abstract ] = $instance;
	}

	public function make( string $abstract ) {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		if ( isset( $this->bindings[ $abstract ] ) ) {
			$concrete = $this->bindings[ $abstract ];
			$instance = $concrete( $this );
			$this->instances[ $abstract ] = $instance;
			return $instance;
		}

		throw new InvalidArgumentException( "No binding found for: {$abstract}" );
	}

	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->instances[ $abstract ] );
	}

	public function resolve( string $abstract, $default = null ) {
		try {
			return $this->make( $abstract );
		} catch ( InvalidArgumentException $e ) {
			return $default;
		}
	}
}

function container(): Container {
	return Container::get_instance();
}