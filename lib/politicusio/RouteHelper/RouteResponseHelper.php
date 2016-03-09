<?php

namespace Politicusio\RouteHelper;

use Slim\Slim;

class RouteResponseHelper
{
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_BAD_REQUEST = 400;

    /**
     * Formats the response to be delivered.
     *
     * @param int $code
     *  The response code.
     * @param int $data
     *  The data to send back.
     * @param bool $error
     *  True if $data is to be treated as errors.
     *
     * @return null
     *  This is the exit method, it should print to the client.
     */
    public static function deliver($code, array $data = array(), $error = false)
    {
        $app = Slim::getInstance();

        // @todo: do we support format suffix? set xml here if so?
        $app->response->headers->set('Content-Type', 'application/vnd.api+json');

        $response = array();

        if (!$error) {
            $response['data'] = $data;
            $app->response->setStatus($code);
            $app->response->setBody(json_encode($response));
        } else {
            $response['errors'] = $data;
            $app->halt($code, json_encode($response));
        }

    }
}

class RouteResponseHelperException extends \Exception {}
