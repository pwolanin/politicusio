<?php

require '../bootstrap.php';

use Slim\Slim;
use Politicusio\Entity\DbEntity;
use Politicusio\Entity\Politician;
use Politicusio\Entity\Fact;
use Politicusio\Entity\Vote;
use Politicusio\RouteHelper\RouteRequestHelper;
use Politicusio\RouteHelper\RouteResponseHelper;

$app = new Slim();

$app->group('/api', function () use ($app) {

    $app->group('/v1', function () use ($app) {

        $app->get('/politicians', function() use ($app) {
            try {
                $politicians = Politician::find(RouteRequestHelper::parseParams($app->request()));
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_OK, $politicians);
            } catch (Exception $e) {
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
            }
        });

        $app->get('/politicians/:id', function($id) use ($app) {
            try {
                $politician = Politician::fromId($id);
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_OK, $politician->asArray());
            } catch (Exception $e) {
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
            }
        });

        // Test: curl -D- -X POST --data '{"name":"some president","creator":"m@asselborn.com","wikipedia":"https://en.wikipedia.org/wiki/cfk","country_id":"1"}' http://localhost:8000/api/v1/politicians
        $app->post(
            '/politicians',
            function() use ($app)
            {
                try {
                    $request = RouteRequestHelper::parseJson($app->request());

                    RouteRequestHelper::isValidEmail($request['creator']);

                    $politician = Politician::create(
                        array(
                            'name' => $request['name'],
                            'creator' => $request['creator'],
                            'wikipedia' => $request['wikipedia'],
                            'country_id' => $request['country_id'],
                            'created' => time(),
                            'updated' => time(),
                            'status' => DbEntity::STATUS_PENDING,
                        )
                    );
                } catch (ErrorException $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array("Your request is missing required properties."), true);
                } catch (Exception $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
                }

                if ($politician->save()) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_CREATED, array());
                }
            }
        );

        $app->get('/politicians/:id/facts', function($id) use ($app) {
            try {
                $politician = Politician::fromId($id);

                $find_query = RouteRequestHelper::parseParams($app->request());
                $find_query['where'][] = array('field' => 'politician_id', 'op' => '=', 'value' => $politician->getId());

                $facts = Fact::find($find_query);

                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_OK, $facts);
            } catch (Exception $e) {
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
            }
        });

        $app->get('/facts/:id', function($id) use ($app) {
            try {
                $fact = Fact::fromId($id);
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_OK, $fact->asArray());
            } catch (Exception $e) {
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
            }
        });

        // Test: curl -D- -X POST --data '{"politician_id":"1","creator":"m@asselborn.com","title":"some crazy title here","description":"some crazy description here","type":"1"}' http://localhost:8000/api/v1/facts
        $app->post(
            '/facts',
            function() use ($app)
            {
                try {
                    $request = RouteRequestHelper::parseJson($app->request());

                    RouteRequestHelper::isValidEmail($request['creator']);

                    $politician = Politician::fromId($request['politician_id']);

                    $fact = Fact::create(
                        array(
                            'politician_id' => $politician->getId(),
                            'creator' => $request['creator'],
                            'title' => $request['title'],
                            'description' => $request['description'],
                            'type' => $request['type'],
                            'created' => time(),
                            'updated' => time(),
                            'status' => DbEntity::STATUS_PENDING,
                        )
                    );
                } catch (ErrorException $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array("Your request is missing required properties."), true);
                } catch (Exception $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
                }

                if ($fact->save()) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_CREATED, array());
                }
            }
        );

        $app->get('/facts/:id/votes', function($id) use ($app) {
            try {
                $fact = Fact::fromId($id);

                $find_query = RouteRequestHelper::parseParams($app->request());
                $find_query['where'][] = array('field' => 'fact_id', 'op' => '=', 'value' => $fact->getId());

                $votes = Vote::find($find_query);

                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_OK, $votes);
            } catch (Exception $e) {
                return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
            }
        });

        // Test: curl -D- -X POST --data '{"fact_id":"1","creator":"m@asselborn.com","type":"1"}' http://localhost:8000/api/v1/votes
        $app->post(
            '/votes',
            function() use ($app)
            {
                try {
                    $request = RouteRequestHelper::parseJson($app->request());

                    RouteRequestHelper::isValidEmail($request['creator']);

                    $fact = Fact::fromId($request['fact_id']);

                    $vote = Vote::create(
                        array(
                            'fact_id' => $fact->getId(),
                            'creator' => $request['creator'],
                            'type' => $request['type'],
                            'created' => (int) time(),
                            'updated' => (int) time(),
                            'status' => DbEntity::STATUS_PENDING,
                        )
                    );
                } catch (ErrorException $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array("Your request is missing required properties."), true);
                } catch (Exception $e) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_BAD_REQUEST, array(get_class($e) . ": " . $e->getMessage()), true);
                }

                if ($vote->save()) {
                    return RouteResponseHelper::deliver(RouteResponseHelper::STATUS_CREATED, array());
                }
            }
        );
    });
});

$app->run();
