<?php

namespace LaraPress\Routing\Matching;

use LaraPress\Http\Request;
use LaraPress\Routing\Route;

class MethodValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param \LaraPress\Routing\Route $route
     * @param \LaraPress\Http\Request $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        return in_array($request->getMethod(), $route->methods());
    }
}
