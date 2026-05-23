<?php
namespace WordPress\Tests;

use WP_Error;
use PHPUnit\Framework\TestCase;

class WP_Error_Test extends TestCase {
	public function test_constructor_empty_code() {
		$error = new WP_Error();
		$this->assertFalse( $error->has_errors() );
		$this->assertEmpty( $error->get_error_codes() );
	}

	public function test_constructor_with_code_and_message() {
		$error = new WP_Error( 'test_error', 'Test error message' );
		$this->assertTrue( $error->has_errors() );
		$this->assertEquals( array( 'test_error' ), $error->get_error_codes() );
		$this->assertEquals( 'test_error', $error->get_error_code() );
		$this->assertEquals( array( 'Test error message' ), $error->get_error_messages( 'test_error' ) );
	}

	public function test_constructor_with_code_message_and_data() {
		$error = new WP_Error( 'test_error', 'Test message', array( 'key' => 'value' ) );
		$this->assertEquals( array( 'key' => 'value' ), $error->get_error_data( 'test_error' ) );
	}

	public function test_get_error_codes_empty() {
		$error = new WP_Error();
		$this->assertEquals( array(), $error->get_error_codes() );
	}

	public function test_get_error_code_empty() {
		$error = new WP_Error();
		$this->assertEquals( '', $error->get_error_code() );
	}

	public function test_get_error_messages_all() {
		$error = new WP_Error();
		$error->add( 'error1', 'Message 1' );
		$error->add( 'error2', 'Message 2' );
		$error->add( 'error1', 'Message 1 duplicate' );

		$messages = $error->get_error_messages();
		$this->assertContains( 'Message 1', $messages );
		$this->assertContains( 'Message 2', $messages );
		$this->assertContains( 'Message 1 duplicate', $messages );
		$this->assertCount( 3, $messages );
	}

	public function test_get_error_messages_specific_code() {
		$error = new WP_Error();
		$error->add( 'error1', 'Message 1' );
		$error->add( 'error1', 'Message 2' );
		$error->add( 'error2', 'Message 3' );

		$this->assertEquals( array( 'Message 1', 'Message 2' ), $error->get_error_messages( 'error1' ) );
		$this->assertEquals( array( 'Message 3' ), $error->get_error_messages( 'error2' ) );
		$this->assertEquals( array(), $error->get_error_messages( 'nonexistent' ) );
	}

	public function test_get_error_message() {
		$error = new WP_Error( 'test', 'First message' );
		$error->add( 'test', 'Second message' );

		$this->assertEquals( 'First message', $error->get_error_message() );
		$this->assertEquals( 'First message', $error->get_error_message( 'test' ) );
		$this->assertEquals( '', $error->get_error_message( 'nonexistent' ) );
	}

	public function test_get_error_data() {
		$error = new WP_Error( 'test', 'message', 'data_value' );
		$this->assertEquals( 'data_value', $error->get_error_data( 'test' ) );
		$this->assertNull( $error->get_error_data( 'nonexistent' ) );
	}

	public function test_add() {
		$error = new WP_Error();
		$error->add( 'new_error', 'New error message' );

		$this->assertTrue( $error->has_errors() );
		$this->assertEquals( array( 'new_error' ), $error->get_error_codes() );
		$this->assertEquals( array( 'New error message' ), $error->get_error_messages( 'new_error' ) );
	}

	public function test_add_multiple_messages_same_code() {
		$error = new WP_Error();
		$error->add( 'same_code', 'First' );
		$error->add( 'same_code', 'Second' );
		$error->add( 'same_code', 'Third' );

		$this->assertEquals( array( 'First', 'Second', 'Third' ), $error->get_error_messages( 'same_code' ) );
	}

	public function test_add_data() {
		$error = new WP_Error();
		$error->add( 'test', 'message' );
		$error->add_data( 'my_data', 'test' );

		$this->assertEquals( 'my_data', $error->get_error_data( 'test' ) );
	}

	public function test_get_all_error_data() {
		$error = new WP_Error();
		$error->add( 'test', 'message', 'data1' );
		$error->add_data( 'data2', 'test' );

		$all_data = $error->get_all_error_data( 'test' );
		$this->assertContains( 'data1', $all_data );
		$this->assertContains( 'data2', $all_data );
	}

	public function test_remove() {
		$error = new WP_Error();
		$error->add( 'error1', 'Message 1' );
		$error->add( 'error2', 'Message 2' );
		$error->remove( 'error1' );

		$this->assertEquals( array( 'error2' ), $error->get_error_codes() );
		$this->assertEquals( array(), $error->get_error_messages( 'error1' ) );
	}

	public function test_merge_from() {
		$source = new WP_Error();
		$source->add( 'source_error', 'Source message' );
		$source->add_data( 'source_data', 'source_error' );

		$target = new WP_Error();
		$target->add( 'target_error', 'Target message' );

		$target->merge_from( $source );

		$this->assertContains( 'target_error', $target->get_error_codes() );
		$this->assertContains( 'source_error', $target->get_error_codes() );
		$this->assertEquals( 'Target message', $target->get_error_message( 'target_error' ) );
		$this->assertEquals( 'Source message', $target->get_error_message( 'source_error' ) );
	}

	public function test_export_to() {
		$source = new WP_Error();
		$source->add( 'source_error', 'Source message' );

		$target = new WP_Error();
		$source->export_to( $target );

		$this->assertContains( 'source_error', $target->get_error_codes() );
		$this->assertEquals( 'Source message', $target->get_error_message( 'source_error' ) );
	}

	public function test_copy_errors() {
		$from = new WP_Error();
		$from->add( 'error1', 'Message 1' );
		$from->add_data( 'data1', 'error1' );

		$to = new WP_Error();
		$to->add( 'error2', 'Message 2' );

		WP_Error::copy_errors( $from, $to );

		$this->assertCount( 2, $to->get_error_codes() );
		$this->assertEquals( 'Message 1', $to->get_error_message( 'error1' ) );
		$this->assertEquals( 'Message 2', $to->get_error_message( 'error2' ) );
	}

	public function test_multiple_errors_different_codes() {
		$error = new WP_Error();
		$error->add( 'code1', 'Message 1' );
		$error->add( 'code2', 'Message 2' );
		$error->add( 'code3', 'Message 3' );

		$codes = $error->get_error_codes();
		$this->assertCount( 3, $codes );
		$this->assertContains( 'code1', $codes );
		$this->assertContains( 'code2', $codes );
		$this->assertContains( 'code3', $codes );
	}
}