<?php namespace Trea\Rest\Facades;
use Illuminate\Support\Facades\Facade;

class RestFacade extends Facade {
	protected static function getFacadeAccessor() { return 'rest'; }
}