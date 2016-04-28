<?php

/**
 * @license Apache 2.0
 */

namespace Swagger\Processors;

use Swagger\Annotations\Get;
use Swagger\Annotations\Path;
use Swagger\Annotations\Post;
use Swagger\Annotations\Property;
use Swagger\Annotations\Put;
use Swagger\Annotations\Response;
use Swagger\Annotations\Schema;
use Swagger\Annotations\Swagger;
use Swagger\Annotations\Definition;
use Swagger\Analysis;
use Traversable;

/**
 * Copy the annotated properties from parent classes;
 */
class HandleReferences
{

    private $responses = [];
    private $head_responses = [];

    public function __invoke(Analysis $analysis)
    {
        /** @var Response $response */
        foreach ($analysis->swagger->responses as $response) {
            $this->responses[$response->response] = [null, $response, []];
        }

        /** @var Get|Put|Post $path */
        foreach ($analysis->swagger->paths as $path) {
            foreach ($path->responses as $response) {
                $this->responses[$response->response] = $this->link($response);
            }
        }

        $this->mapResponses();
        $this->importReferences();
    }

    private function link($response) {
        return [null, $response, []];
    }

    private function mapResponses()
    {
        foreach ($this->responses as &$data) {
            /** @var Response $response */
            $response = $data[1];

            if (preg_match('/^\$/',$response->ref)) {
                $params = explode("/", strtolower($response->ref));

                $this->loadParent($data, $params[1], $params[2]);
            } else {
                $this->head_responses[] = &$data;
            }
        }
    }

    private function loadParent(&$data, $type, $name) {
        if (isset($this->$type) && isset($this->{$type}[$name])) {
            $data[0] = &$this->{$type}[$name];
            $this->{$type}[$name][2][] = &$data;
        }
    }

    private function importReferences()
    {
        $queue = $this->head_responses;

        while (count($queue)) {
            $this->iterateQueue($queue);
        }
    }

    private function iterateQueue(&$queue)
    {
        $item = array_pop($queue);

        $queue = array_merge($queue, $item[2]);

        /** @var Response $response */
        $response = $item[1];
        /** @var Response $parent_response */
        $parent_response = $item[0];

        if (!is_null($parent_response)) {
            foreach ($parent_response as $key => $value) {
                if ($key == "schema") {
                    $this->importSchema($parent_response->schema, $response->schema);
                } else if ($key != "response") {
                    if (is_array($value)) {
                        $response->$key = array_merge($response->$key, $parent_response->$key);
                    } else {
                        $response->$key = $parent_response->$key;
                    }
                }
            }
        }
    }

    private function importSchema(Schema $parent, Schema $child)
    {
        foreach ($parent as $key => $value) {
            if ($key == "properties") {

            } else {

            }
        }
    }


    private function handle(Response $response)
    {

    }
}
