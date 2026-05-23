<?php
namespace WordPress\Tests;

use PHPUnit\Framework\TestCase;

class Formatting_Test extends TestCase {
	public function test_sanitize_key_basic() {
		$this->assertEquals( 'my-key', sanitize_key( 'my-key' ) );
		$this->assertEquals( 'my_key', sanitize_key( 'my_key' ) );
		$this->assertEquals( 'abc123', sanitize_key( 'abc123' ) );
	}

	public function test_sanitize_key_uppercase_to_lowercase() {
		$this->assertEquals( 'my-key', sanitize_key( 'MY-KEY' ) );
		$this->assertEquals( 'my_key', sanitize_key( 'MY_KEY' ) );
		$this->assertEquals( 'abc123', sanitize_key( 'ABC123' ) );
	}

	public function test_sanitize_key_removes_special_chars() {
		$this->assertEquals( 'mykey', sanitize_key( 'my@key!' ) );
		$this->assertEquals( 'key123', sanitize_key( 'key!@#$%123' ) );
		$this->assertEquals( 'test-value', sanitize_key( 'test-value' ) );
	}

	public function test_sanitize_key_empty_string() {
		$this->assertEquals( '', sanitize_key( '' ) );
	}

	public function test_sanitize_key_non_scalar() {
		$this->assertEquals( '', sanitize_key( null ) );
		$this->assertEquals( '', sanitize_key( array() ) );
		$this->assertEquals( '', sanitize_key( new \stdClass() ) );
	}

	public function test_sanitize_key_mixed_valid_invalid() {
		$this->assertEquals( 'abc123', sanitize_key( 'abc@#$%123!@' ) );
		$this->assertEquals( 'a-b-c', sanitize_key( 'a@b#c' ) );
	}

	public function test_sanitize_html_class_valid() {
		$this->assertEquals( 'my-class', sanitize_html_class( 'my-class' ) );
		$this->assertEquals( 'MyClass', sanitize_html_class( 'MyClass' ) );
		$this->assertEquals( 'class-123', sanitize_html_class( 'class-123' ) );
	}

	public function test_sanitize_html_class_removes_invalid_chars() {
		$this->assertEquals( 'myclass', sanitize_html_class( 'my@class!' ) );
		$this->assertEquals( 'class123', sanitize_html_class( 'class!@#$123' ) );
	}

	public function test_sanitize_html_class_percent_encoded() {
		$this->assertEquals( 'class', sanitize_html_class( 'class%20name' ) );
		$this->assertEquals( 'test', sanitize_html_class( 'test%2Fpath' ) );
	}

	public function test_sanitize_html_class_empty_with_fallback() {
		$this->assertEquals( 'fallback', sanitize_html_class( '', 'fallback' ) );
		$this->assertEquals( 'default-class', sanitize_html_class( '@!#', 'default-class' ) );
	}

	public function test_sanitize_html_class_empty_no_fallback() {
		$result = sanitize_html_class( '' );
		$this->assertEquals( '', $result );
	}

	public function test_sanitize_hex_color_with_hash() {
		$this->assertEquals( '#fff', sanitize_hex_color( '#fff' ) );
		$this->assertEquals( '#ffffff', sanitize_hex_color( '#ffffff' ) );
		$this->assertEquals( '#ABC123', sanitize_hex_color( '#ABC123' ) );
	}

	public function test_sanitize_hex_color_without_hash_rejected() {
		$this->assertNull( sanitize_hex_color( 'fff' ) );
		$this->assertNull( sanitize_hex_color( 'ffffff' ) );
	}

	public function test_sanitize_hex_color_invalid() {
		$this->assertNull( sanitize_hex_color( '#gggggg' ) );
		$this->assertNull( sanitize_hex_color( '#ff' ) );
		$this->assertNull( sanitize_hex_color( '#fffffff' ) );
		$this->assertNull( sanitize_hex_color( 'not-a-color' ) );
	}

	public function test_sanitize_hex_color_empty() {
		$this->assertEquals( '', sanitize_hex_color( '' ) );
	}

	public function test_sanitize_hex_color_mixed_case() {
		$this->assertEquals( '#fff', sanitize_hex_color( '#FFF' ) );
		$this->assertEquals( '#aabbcc', sanitize_hex_color( '#AaBbCc' ) );
	}
}