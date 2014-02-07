<?php namespace Trea\Rest;

use \Illuminate\Http\Response;
use \Exception;

class Rest
{
    const VERSION = 0.2;
    protected $app;
    protected $response;

    protected $result;
    protected $errors = [];
    protected $warnings = [];

    protected $pagination;

    protected $headers = [];

    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Sets the current page for pagination chunk of Response body
     * @param  integer $page Current Page Number
     * @return [type]       [description]
     */
    public function currentPage($page)
    {
        $this->pagination['current'] = (int) $page;
        return $this;
    }

    public function of ($pages)
    {
        $this->pagination['pagesCount'] = (int) $pages;
        return $this;
    }

    public function totalItems($total)
    {
        $this->pagination['totalItems'] = (int) $total;
        return $this;
    }

    public function limited($items)
    {
        $this->pagination['displayItems'] = (int) $items;
        return $this;
    }

    /**
     * Adds errors to be communicated in JSON response
     * @param  Array  $errors Errors associated with query
     * @return self
     */
    public function withErrors(Array $errors)
    {
        if (count($this->errors) > 0) {
            $this->errors = array_merge($this->errors, $errors);
        } else {
            $this->errors = $errors;
        }
        return $this;
    }

    /**
     * Adds warnings to be communicated in JSON response
     * @param  Array  $warnings Warnings associated with query
     * @return self
     */
    public function withWarnings(Array $warnings)
    {
        if (count($this->warnings) > 0) {
            $this->warnings = array_merge($this->warnings, $warnings);
        } else {
            $this->warnings = $warnings;
        }
        return $this;
    }

    /**
     * Adds HTTP Headers to Responseq
     * @param  Array  $headers Headers for HTTP Response
     * @return self
     */
    public function withHeaders(Array $headers)
    {
        if (count($this->headers) > 0) {
            $this->headers = array_merge($this->headers, $headers);
        } else {
            $this->headers = $headers;
        }
        return $this;
    }

    /**
     * Returns 200 Okay along with query results
     * @param  Mixed  $result Results of query
     * @return Response
     */
    public function okay($result = null)
    {
        return $this->respond($result, 200);
    }

    /**
     * Returns a 201 Created with results
     * @param  Array $result   Result
     * @param  String $location Location of new resource
     * @return Response
     */
    public function created($result = null, $location = null)
    {
        if ($location !== null) {
            return $this->withHeaders(['Location' => $location])->respond($result, 201);
        } else {
            return $this->respond($result, 201);
        }
    }

    /**
     * Returns a 202 Accepted along with query results
     * @param  Array $result Query Result
     * @return Response
     */
    public function accepted($result = null)
    {
        return $this->respond($result, 202);
    }

    /**
     * Returns a 302 Found with the new URL
     * @param  String $location Location/URI to find resource at
     * @return Response
     */
    public function found($location)
    {
        return $this->withHeaders(['Location' => $location])->respond(null, 302);
    }

    /**
     * Returns a 400 Bad Request; Reasoning behind the rejection should be provided via withErrors()
     * @return Response
     */
    public function badRequest()
    {
        return $this->respond(null, 400);
    }

    /**
     * Returns 401 Unauthorized
     * @return Response
     */
    public function unauthorized()
    {
        return $this->respond(null, 401);
    }

    /**
     * Returns a 403 Forbidden;  Reasoning behind the rejection should be provided via withErrors()
     * @return [type] [description]
     */
    public function forbidden()
    {
        return $this->response(null, 403);
    }

    /**
     * Returns a 404 Not Found response with JSON message
     * @return Response
     */
    public function notFound()
    {
        return $this->respond(null, 404, ['not_found' => 'The resource requested could not be found at this URI.']);
    }

    /**
     * Returns a 409 Conflict;  Reasoning behind the rejection should be provided via withErrors()
     * @return Response
     */
    public function conflict()
    {
        return $this->respond(null, 409);
    }

    /**
     * Returns a 410 Gone
     * @return Response
     */
    public function gone()
    {
        return $this->respond(null, 410);
    }

    /**
     * Returns a 415 Unsupported Media;  Reasoning behind the rejection should be provided via withErrors()
     * @return Response
     */
    public function unsupportedMedia()
    {
        return $this->respond(null, 415);
    }
    
    /**
     * Returns a 501 Not Implemented response with JSON message
     * @return Response
     */
    public function notImplemented ()
    {
        return $this->respond(null, 501, ['not_implemented' => 'This URI is not yet implemented.']);
    }

    /**
     * Returns a 405 Method Not Allowed response with JSON message
     * @return Response
     */
    public function methodNotAllowed()
    {
        return $this->respond(null, 405, ['method_not_allowed' => 'Method type not allowed on resource.']);
    }

    /**
     * Returns a 500 Internal Server Error response with JSON message
     * If a more applicable HTTP status can be used, use that instead.
     * @return Response
     */
    public function error()
    {
        return $this->respond(null, 500, ['internal_server_error' => 'The server encountered an unexpected condition which prevented it from fulfilling the request.']);
    }

    /**
     * Builds a JSON response including results, errors, and warnings
     * @param  array $result   Result of query
     * @param  integer $status   HTTP Response Code
     * @param  array $errors   Any errors attributed to the request
     * @param  array $warnings Any warnings attributed to the request
     * @return Response
     */
    private function respond($result, $status, $errors = [], $warnings = [])
    {
        $result = $this->transformInput($result);

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

    /**
     * transformInput() takes the result passed into the given method
     * and checks for whether or not it is A) an array, B) a Laravel paginator
     * instance, C) a Laravel Eloquent collection, or D) another object that
     * implements a toArray() method that we can call to get a proper array of the data.
     *
     * If an array is passed, it is simply returned.
     * 
     * If a Paginator instance is passed, all of the pagination data for the
     * response is automatically setup.
     *
     * If an Eloquent Collection is passed, toArray() is simply called upon it to get the data.
     *
     * If another object implementing a toArray() method is passed, the method will be invoked
     * to get the data.
     *
     * If none of the above applies, an Exception will be thrown
     *
     * @param  mixed  $input Input to be processed
     * @return Array         Returns an array to use in response.
     *
     * @throws Exception     If $input is an unsupported type.
     */
    private function transformInput($input)
    {
        if (is_array($input) || is_null($input)) {
         
            return $input;
        
        } elseif (is_a($input, "Illuminate\Pagination\Paginator")) {
            $this->currentPage($input->getCurrentPage());
            $this->of($input->getLastPage());
            $this->totalItems($input->getTotal());
            $this->limited($input->getPerPage());

            return $input->toArray()['data'];

        } elseif (is_a($input, "Illuminate\Database\Eloquent\Collection")) {
           
            return $input->toArray();
       
        } elseif (is_object($input) && method_exists($input, 'toArray')) {
           
            return $input->toArray();
        
        } else {
        
            throw new Exception("Unsupported type passed to Rest response");
        
        }
    }
}
