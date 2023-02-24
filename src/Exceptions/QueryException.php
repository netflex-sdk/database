<?php

namespace Netflex\Database\Exceptions;

use Illuminate\Database\QueryException as Exception;
use Throwable;

class QueryException extends Exception
{
    /**
     * Format the SQL error message.
     *
     * @param string $query
     * @param array $bindings
     * @param Throwable $previous
     * @return string
     */
    protected function formatMessage($query, $bindings, Throwable $previous)
    {
        if ($parsed = json_decode($query)) {
            $action = $parsed->action ?? null;

            if ($request = ($parsed->request ?? null)) {
                if (isset($request->body) || isset($request->aggs)) {
                    return $previous->getMessage() . ' (Query: ' . json_encode($request) . ')';
                }
            }

            if ($action) {
                return $previous->getMessage() . ' [' . $action . ']';
            }
        }

        return $previous->getMessage();
    }
}
