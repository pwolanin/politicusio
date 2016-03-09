<?php

namespace Politicusio\RouteHelper;

use Slim\Slim;
use Slim\Http\Request;

class RouteRequestHelper
{
    /**
     * Decodes the request JSON string to an array.
     *
     * @param \Slim\Http\Request $request
     *  The request object.
     *
     * @return array
     *  The parsed JSON string into an array.
     */
    public static function parseJson(Request $request)
    {
        $body = json_decode($request->getBody(), true);
        
        if (!is_array($body)) {
            throw new RouteRequestHelperException('Invalid JSON');
        }

        return $body;
    }

    /**
     * Translates query parameters into a proper DbEntity::find() query array.
     *
     * @param \Slim\Http\Request $request
     *  The request object.
     *
     * @return array
     *  The query array to feed to DbEntity::find().
     */
    public static function parseParams(Request $request)
    {
        $query = array();
        $params = $request->params();
        
        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (isset($params['offset'])) {
            $query['offset'] = $params['offset'];
        }

        // @todo: rewrite this to be order_by=-field for asc and order_by=field for desc
        foreach (array('desc', 'asc') as $param) {
            if (isset($params['sort_' . $param])) {
                $query['order_by'] = array(
                    'field' => $params['sort_' . $param],
                    'order' => $param,
                );
            }
        }

        foreach (array('name', 'title', 'description') as $param) {
            if (isset($params[$param . '_like'])) {
                $query['where'][] = array('field' => $param, 'op' => 'LIKE', 'value' => "%" . $params[$param . '_like'] . "%");
            }
        }

        foreach (array('created', 'updated') as $param) {
            if (isset($params[$param . '_after'])) {
                $query['where'][] = array('field' => $param, 'op' => '>', 'value' => $params[$param . '_after']);
            }

            if (isset($params[$param . '_before'])) {
                $query['where'][] = array('field' => $param, 'op' => '<', 'value' => $params[$param . '_before']);
            }
        }

        foreach (array('status', 'country_id', 'type') as $param) {
            if (isset($params[$param])) {
                $query['where'][] = array('field' => $param, 'op' => '=', 'value' => $params[$param]);
            }
        }

        return $query;
    }

    /**
     * Does a basic check on email format, but doesn't guarantee that the email
     * address exists.
     *
     * @param string $email
     *  The email address to check.
     *
     * @throws RouteRequestHelperException
     *  When the email address isn't valid.
     */
    public static function isValidEmail($email)
    {
        if (!$email) {
            throw new RouteValidationHelperException('Creator email is not set.');
        }

        // Valid format?
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RouteRequestHelperException('Not a valid email format.');
        }

        // Go over known disposable email addresses and fail.
        // @todo: grab these domains from https://github.com/WhiteHouse/_petitions/tree/f8fc90af3ee3619074bc4ea7fcafb81317d10018/docroot/sites/all/libraries/disposable_email_checker/data
        // stick them in a file or table and iterate over them.
        $domain = explode('@', $email)[1];
        if (in_array($domain, array(/* here we load the bad domains */))) {
            throw new RouteRequestHelperException('Email domain is not supported.');
        }
    }
}

class RouteRequestHelperException extends \Exception {}