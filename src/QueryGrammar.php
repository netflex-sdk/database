<?php

namespace Netflex\Database;

use RuntimeException;

use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use Netflex\Query\Builder as QueryBuilder;

class QueryGrammar extends Grammar
{
    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The grammar specific bitwise operators.
     *
     * @var array
     */
    protected $bitwiseOperators = [];

    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'wheres',
        'offset',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Compile a select query into a ElasticSearch request.
     *
     * @param Builder $query
     * @return array
     */
    public function compileSelect(Builder $query)
    {
        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $request = array_merge(
            $this->concatenate(
                $this->compileComponents($query)
            )
        );

        return $request;
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $query
     * @param array $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $method = 'compileAggregate' . ucfirst($aggregate['function']);

        return [
            'body' => [
                'aggs' => [
                    'aggregate' => $this->$method($query, $aggregate)
                ]
            ]
        ];
    }

    protected function compileAggregateCount(Builder $query, $aggregate)
    {
        if ($aggregate['columns'] === ['*']) {
            $aggregate['columns'] = ['_uid'];
        }

        if ($query->distinct) {
            return $this->compileAggregateCardinality($query, $aggregate);
        }

        return [
            'value_count' => [
                'field' => $aggregate['columns'][0]
            ]
        ];
    }

    protected function compileAggregateAvg(Builder $query, $aggregate)
    {
        return [
            'avg' => [
                'field' => $aggregate['columns'][0]
            ]
        ];
    }

    protected function compileAggregateCardinality(Builder $query, $aggregate)
    {
        return [
            'cardinality' => [
                'field' => $aggregate['columns'][0]
            ]
        ];
    }

    protected function compileAggregateMin(Builder $query, $aggregate)
    {
        return [
            'min' => [
                'field' => $aggregate['columns'][0]
            ]
        ];
    }

    protected function compileAggregateMax(Builder $query, $aggregate)
    {
        return [
            'max' => [
                'field' => $aggregate['columns'][0]
            ]
        ];
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param array $segments
     * @return array
     */
    protected function concatenate($segments)
    {
        return $segments;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param Builder $query
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $request = Collection::make();

        if (is_null($query->limit)) {
            $query->limit = QueryBuilder::MAX_QUERY_SIZE;
        }

        foreach ($this->selectComponents as $component) {
            if (isset($query->$component)) {
                $method = 'compile' . ucfirst($component);

                if ($compiled = $this->$method($query, $query->$component)) {
                    $request = $request->mergeRecursive($compiled);
                }
            }
        }

        return $request->mapWithKeys(function ($value, $key) {
            if (in_array($key, ['index', 'size', 'from']) && is_array($value)) {
                $value = end($value);
            }

            return [$key => $value];
        })->all();

        return $request->all();
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param Builder $query
     * @param array $columns
     * @return array|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        if (empty($columns) || in_array('*', $columns)) {
            return null;
        }

        return ['_source' => $columns];
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param Builder $query
     * @param string $table
     * @return array
     */
    protected function compileFrom(Builder $query, $table)
    {
        return ['index' => $this->wrapTable($table)];
    }

    protected function wrapValue($value)
    {
        return $value;
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param Builder $query
     * @return array
     */
    public function compileWheres(Builder $query)
    {
        $body = ['track_scores' => true];

        if (count($compiled = $this->compileWheresToArray($query)) > 0) {
            if ($compiledQuery = $this->concatenateWhereClauses($query, $compiled)) {
                $body['query'] =  [
                    'query_string' => [
                        'query' => $compiledQuery
                    ]
                ];
            }
        }

        return ['body' => $body];
    }

    /**
     * Compile a "where null" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        return 'NOT ' . $this->whereNotNull($query, $where);
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        return '_exists_:' . $where['column'];
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param Builder $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            if ($compiled = $this->{"where{$where['type']}"}($query, $where)) {
                return Str::upper($where['boolean']) . ' (' . $compiled . ')';
            }

            return '';
        })->all();
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param Builder $query
     * @param array $sql
     * @return string
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        return $this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a basic where clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        $builder = new QueryBuilder(false, []);
        $where['value'] = str_replace('%', '*', $where['value']);

        if ($where['type'] !== 'like') {
            $where['value'] = str_replace('*', '', $where['value']);
        }

        return $builder
            ->where($where['column'], $where['operator'], $where['value'])
            ->getQuery();
    }

    protected function whereIn(Builder $query, $where)
    {
        if (count($where['values']) > 0) {
            $builder = new QueryBuilder(false, []);

            return $builder
                ->where($where['column'], $where['values'])
                ->getQuery();
        }

        return '';
    }

    protected function whereBetween(Builder $query, $where)
    {
        $builder = new QueryBuilder(false, []);

        return $builder
            ->where($where['column'], '>=', $where['values'][0])
            ->where($where['column'], '<=', $where['values'][1])
            ->getQuery();
    }

    /**
     * Compile a "where in raw" clause.
     *
     * For safety, whereIntegerInRaw ensures this method is only used with integer values.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    protected function whereInRaw(Builder $query, $where)
    {
        if (!empty($where['values'])) {
            return $where['column'] . ':(' . implode(' ', $where['values']) . ')';
        }

        return '';
    }

    public function whereFullText(Builder $query, $where)
    {
        return collect($where['columns'])->map(function ($column) use ($query, $where) {
            $compiledColumn = collect(explode(' ', $where['value']))
                ->map(function ($word) use ($query, $column) {
                    return $this->whereBasic($query, [
                        'type' => 'Basic',
                        'boolean' => 'or',
                        'column' => $column,
                        'operator' => 'like',
                        'value' => '*' . $word . '*',
                    ]);
                })->implode(' AND ');
            return '(' . $compiledColumn . ')';
        })->implode(' OR ');
    }

    protected function whereExists(Builder $query, $where)
    {
        throw new RuntimeException('This database engine does not support the [whereExists] operation.');
    }

    protected function whereColumn(Builder $query, $where)
    {
        throw new RuntimeException('This database engine does not support the [whereBetweenColumns] operation.');
    }

    protected function whereBetweenColumns(Builder $query, $where)
    {
        throw new RuntimeException('This database engine does not support the [whereBetweenColumns] operation.');
    }

    /**
     * Compile a date based where clause.
     *
     * @param string $type
     * @param Builder $query
     * @param array $where
     * @return string
     */
    public function dateBasedWhere($type, $query, $where)
    {
        $builder = new QueryBuilder(false, []);
        $value = (string) $where['value'];

        if (in_array($where['type'], ['Month', 'Day', 'Time'])) {
            throw new RuntimeException('This database engine does not support the [where' . $where['type'] . '] operation.');
        }

        if ($where['type'] === 'Year') {
            return $builder->whereBetween($where['column'], $value . '-01-01', $value . '-12-31')
                ->getQuery();
        }

        return $builder
            ->where($where['column'], $where['operator'], $value)
            ->getQuery();
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param Builder $query
     * @param array $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        $body = [];
        $compiled = [];

        foreach ($orders as $order) {
            if (strtolower($order['type'] ?? 'column') === 'raw') {
                $order['column'] = '_score';
                $body['query'] = $order['sql'];
            }

            $column = $order['column'];

            $compiled[] = [
                $column => [
                    'order' => $order['direction'] ?? 'asc'
                ]
            ];
        }

        if (count($compiled)) {
            $body['sort'] = $compiled;
        }

        return ['body' => $body];
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param string $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        if (!$seed) {
            $seed = time();
        }

        return [
            'function_score' => [
                'random_score' => [
                    'seed' => $seed
                ]
            ]
        ];
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $query
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        if (isset($query->limit) && count($query->aggregate ?? [])) {
            $limit = 0;
        }

        return [
            'size' => $limit ?? 10000
        ];
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param Builder $query
     * @param int $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return ['from' => $offset];
    }
}
