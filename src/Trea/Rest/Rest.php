<?php namespace Trea\Rest;

use \Illuminate\Http\Response;

class Rest {
	const VERSION = 0.1;
	protected $app;
	protected $response;

	protected $result;
	protected $errors = [];
	protected $warnings = [];

	protected $pagination;

	protected $headers = [];

	function getVersion() {
		return self::VERSION;
	}

	function currentPage($page) {
		$this->pagination['current'] = (int) $page;
		return $this;
	}

	function of ($pages) {
		$this->pagination['pagesCount'] = (int) $pages;
		return $this;
	}

	function totalItems($total) {
		$this->pagination['totalItems'] = (int) $total;
		return $this;
	}

	function limited($items) {
		$this->pagination['displayItems'] = (int) $items;
		return $this;
	}

	/**
	 * Adds errors to be communicated in JSON response
	 * @param  Array  $errors Errors associated with query
	 * @return self
	 */
	function withErrors(Array $errors) {
		if (count($this->errors) > 0) {
			$this->errors = array_merge($this->errors, $errors);
		}
		else {
			$this->errors = $errors;
		}
		return $this;
	}

	/**
	 * Adds warnings to be communicated in JSON response
	 * @param  Array  $warnings Warnings associated with query
	 * @return self
	 */
	function withWarnings(Array $warnings) {
		if (count($this->warnings) > 0) {
			$this->warnings = array_merge($this->warnings, $warnings);
		}
		else {
			$this->warnings = $warnings;
		}
		return $this;
	}

	/**
	 * Adds HTTP Headers to Responseq
	 * @param  Array  $headers Headers for HTTP Response
	 * @return self
	 */
	function withHeaders(Array $headers) {
		if (count($this->headers) > 0) {
			$this->headers = array_merge($this->headers, $headers);
		}
		else {
			$this->headers = $headers;
		}
		return $this;
	}

	/**
	 * Returns 200 Okay along with query results
	 * @param  Array  $result Results of query
	 * @return Response
	 */
	function okay(Array $result = null) {
		return $this->respond($result, 200);
	}

	/**
	 * Returns a 201 Created with results
	 * @param  Array $result   Result
	 * @param  String $location Location of new resource
	 * @return Response
	 */
	function created(Array $result = null, $location = null) {
		if ($location !== null) {
			return $this->withHeaders(['Location' => $location])->respond($result, 201);
		}
		else {
			return $this->respond($result, 201);
		}
	}

	/**
	 * Returns a 202 Accepted along with query results
	 * @param  Array $result Query Result
	 * @return Response
	 */
	function accepted(Array $result = null) {
		return $this->respond($result, 202);
	}

	/**
	 * Returns a 302 Found with the new URL
	 * @param  String $location Location/URI to find resource at
	 * @return Response
	 */
	function found($location) {
		return $this->withHeaders(['Location' => $location])->respond(null, 302);
	}

	/**
	 * Returns a 400 Bad Request; Reasoning behind the rejection should be provided via withErrors()
	 * @return Response
	 */
	function badRequest() {
		return $this->respond(null, 400);
	}

	/**
	 * Returns 401 Unauthorized
	 * @return Response
	 */
	function unauthorized() {
		return $this->respond(null, 401);
	}

	/**
	 * Returns a 403 Forbidden;  Reasoning behind the rejection should be provided via withErrors()
	 * @return [type] [description]
	 */
	function forbidden() {
		return $this->response(null, 403);
	}

	/**
	 * Returns a 404 Not Found response with JSON message
	 * @return Response
	 */
	function notFound() {
		return $this->respond(null, 404,  ['not_found' => 'The resource requested could not be found at this URI.']);
	}

	/**
	 * Returns a 409 Conflict;  Reasoning behind the rejection should be provided via withErrors()
	 * @return Response
	 */
	function conflict() {
		return $this->respond(null, 409);
	}

	/**
	 * Returns a 410 Gone
	 * @return Response
	 */
	function gone() {
		return $this->respond(null, 410);
	}

	/**
	 * Returns a 415 Unsupported Media;  Reasoning behind the rejection should be provided via withErrors()
	 * @return Response
	 */
	function unsupportedMedia() {
		return $this->respond(null, 415);
	}
	
	/**
	 * Returns a 501 Not Implemented response with JSON message
	 * @return Response
	 */
	function notImplemented () {
		return $this->respond(null, 501, ['not_implemented' => 'This URI is not yet implemented.']);
	}


	/**
	 * Builds a JSON response including results, errors, and warnings
	 * @param  array $result   Result of query
	 * @param  integer $status   HTTP Response Code
	 * @param  array $errors   Any errors attributed to the request
	 * @param  array $warnings Any warnings attributed to the request
	 * @return Response
	 */
	private function respond($result, $status, $errors = [], $warnings = []) {
		$response = [
			'result' => $result
		];

		if (count($this->errors) > 0  || count($errors) > 0) {
			$response['errors'] = (array) array_merge($this->errors, $errors);
		}
		if (count($this->warnings) > 0 || count($warnings) > 0) {
			$response['warnings'] = (array) array_merge($this->warnings, $warnings);
		}

		if (is_array($this->pagination)) {
			$response['pagination'] = $this->pagination;
		}

		$body = json_encode(array_merge(['result' => $result], $response));
		$headers = array_merge($this->headers, ['Content-Type' => 'application/json']);
		return new Response($body, $status, $this->headers);
	}
}