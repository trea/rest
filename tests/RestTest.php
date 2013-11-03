<?php
class RestTest extends \PHPUnit_Framework_TestCase {
	
	function setUp() {
		$this->rest = new Trea\Rest\Rest();
	}

	function testGetVersion() {
		$this->assertEquals(0.1, $this->rest->getVersion());
	}

	function testOkay() {
		$json = [
			"result" => [
				'foo' => 'bar'
			]
		];

		$response = $this->rest->okay(['foo' => 'bar']);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Reponse from okay().");
	}

	function testOkayWithWarnings() {
		$result = [
			'foo' => 'bar'
		];

		$warnings = [
			'foo' => "Unknown field 'foo' skipped on input."
		];

		$json = [
			"result" => (array) $result,
			"warnings" => (array) $warnings
		];

		$response = $this->rest->withWarnings($warnings)->okay($result);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withWarnings()->okay()");
	}

	function testOkayWithError() {
		$result = [
			'foo' => 'bar'
		];

		$errors = [
			'foo' => "Unkown field 'foo' skipped on input."
		];

		$json = [
			"result" => (array) $result,
			"errors" => (array) $errors,
		];

		$response = $this->rest->withErrors($errors)->okay($result);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withErrors()->okay()");
	}

	function testOkayWithErrorAndWarning() {
		$result = [
			'foo' => 'bar'
		];

		$errors = [
			'foo' => "Unkown field 'foo' skipped on input."
		];
		
		$warnings = [
			'foo' => "Unknown field 'foo' skipped on input."
		];

		$json = [
			"result" => (array) $result,
			"errors" => (array) $errors,
			"warnings" => (array) $warnings
		];

		$response = $this->rest->withWarnings($warnings)->withErrors($errors)->okay($result);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withWarnings()->withErrors()->okay()");	
	}

	function testOkayMultipleWarning() {
		$result = [
			'foo' => 'bar'
		];

		$warning = [
			'foo' => "Unkown field 'foo' skipped on input."
		];
		
		$warning2 = [
			'foo2' => "Unknown field 'foo2' skipped on input."
		];

		$json = [
			"result" => (array) $result,
			"warnings" => (array) array_merge($warning, $warning2)
		];

		$response = $this->rest->withWarnings($warning)->withWarnings($warning2)->okay($result);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withWarnings()->withWarnings()->okay()");		
	}

	function testOkayWithHeader() {
		$result = [
			'foo' => 'bar'
		];

		$headers = [
			'Location' => 'http://localhost'
		];

		$json = [
			"result" => (array) $result,
		];

		$response = $this->rest->withHeaders($headers)->okay($result);

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('http://localhost', $response->headers->get('Location'));
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withHeaders()->okay()");
	}

	function testAccepted() {
		$result = [
			'foo' => 'bar'
		];

		$headers = [
			'Location' => 'http://localhost'
		];

		$json = [
			"result" => (array) $result,
		];

		$response = $this->rest->withHeaders($headers)->accepted($result);

		$this->assertEquals(202, $response->getStatusCode());
		$this->assertEquals('http://localhost', $response->headers->get('Location'));
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from withHeaders()->accepted()");	
	}

	function testCreated() {
		$result = [
			'foo' => 'bar'
		];

		$json = [
			"result" => (array) $result,
		];

		$response = $this->rest->created($result, 'http://localhost/foo/1');

		$this->assertEquals(201, $response->getStatusCode());
		$this->assertEquals('http://localhost/foo/1', $response->headers->get('Location'));
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from created()");		
	}

	function testCreatedWithNoURL() {
		$result = [
			'foo' => 'bar'
		];

		$json = [
			"result" => (array) $result,
		];

		$response = $this->rest->created($result);

		$this->assertEquals(201, $response->getStatusCode());
		$this->assertFalse($response->headers->has('Location'));
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from created()");		
	}

	function testNotFound() {
		$json = [
			"result" => null,
			"errors"=> [
				"not_found" => "The resource requested could not be found at this URI."
			],
		];

		$response = $this->rest->notFound();
		$this->assertEquals(404, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Reponse from notFound().");
	}

	function testConflict() {
		$response = $this->rest->conflict();
		$this->assertEquals(409, $response->getStatusCode());
	}

	function testNotImplemented() {
		$json = [
			'result' => NULL,
			'errors' => [
				'not_implemented' => 'This URI is not yet implemented.'
			],
		];

		$response = $this->rest->notImplemented();
		$this->assertEquals(501, $response->getStatusCode());
		$this->assertJsonStringEqualsJsonString(json_encode($json), $response->getContent(), "Invalid JSON Response from notImplemented()");
	}
}