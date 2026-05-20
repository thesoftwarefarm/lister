<?php

namespace TsfCorp\Lister;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;
use TsfCorp\Lister\Exceptions\ListerException;
use TsfCorp\Lister\Filters\ListerFilter;

class Lister
{
    private Request $request;
    private Connection $db;
    private Collection $filters;
    public ?LengthAwarePaginator $results = null;
    private array $query_settings = [];

    private int $results_per_page;
    private int $current_page;
    private int $offset;
    private string $sql_without_limits = '';

    public function __construct(Request $request, Connection $db)
    {
        $this->request = $request;
        $this->db = $db;
        $this->filters = new Collection();

        $this->current_page = (int)$this->request->input('page', 1);
        $this->results_per_page = (int)$this->request->input('rpp', config('lister.results_per_page', 20));
        $this->offset = $this->computeOffset();
    }

    public function make(array $query_settings): static
    {
        $this->query_settings = $query_settings;

        return $this;
    }

    public function setConnection(string|Connection $connection): static
    {
        if (is_string($connection)) {
            $this->db = DB::connection($connection);
        } else if ($connection instanceof Connection) {
            $this->db = $connection;
        }

        return $this;
    }

    public function getConnection(): Connection
    {
        return $this->db;
    }

    private function computeOffset(): int
    {
        if ($this->current_page == 1) {
            return 0;
        }

        return $this->results_per_page * $this->current_page - $this->results_per_page;
    }

    public function addFilter(ListerFilter $filter): static
    {
        $this->filters->push([
            'type' => 'where',
            'filter' => $filter,
        ]);

        return $this;
    }

    public function addHavingFilter(ListerFilter $filter): static
    {
        $this->filters->push([
            'type' => 'having',
            'filter' => $filter,
        ]);

        return $this;
    }

    public function get(): static
    {
        $paginated_results = new LengthAwarePaginator(
            $this->fetchRecords(),
            $this->fetchTotal(),
            $this->getResultsPerPage(),
            $this->current_page,
            [
                'path' => $this->request->url(),
            ]
        );

        $this->results = $paginated_results;

        return $this;
    }

    private function fetchRecords(): array|\Illuminate\Database\Eloquent\Collection
    {
        try {
            $results = $this->db->select($this->buildQuery());
        } catch (Throwable $t) {
            $this->forgetFilters();

            throw new ListerException($t->getMessage(), (int) $t->getCode(), $t);
        }

        $model = !empty($this->query_settings['model']) ? $this->query_settings['model'] : null;

        if ($model && class_exists($model)) {
            return $model::hydrate($results);
        }

        return $results;
    }

    private function buildQuery(): string
    {
        // add where clause to query body
        $where_clause = $this->buildConditionsSql($this->filters->filter(function ($entry) {
            return $entry['type'] === 'where';
        })->map(function ($entry) {
            return $entry['filter'];
        }));

        $query = $this->query_settings['body'];

        // add where?
        preg_match('/where\r?\s+(.+\r*\s+and\r*\s+)*\{filters\}/i', $query, $where_matches);

        if (count($where_clause)) {
            $append_where = count($where_matches) ? '' : ' WHERE ';
            $query = str_replace('{filters}', $append_where . implode(' AND ', $where_clause), $query);
        } else {
            $empty_filter = count($where_matches) ? ' (1) ' : '';
            $query = str_replace('{filters}', $empty_filter, $query);
        }

        // build query with fields and body
        $query = sprintf('SELECT %s %s', $this->query_settings['fields'], $query);

        // add having clause
        $having_clause = $this->buildConditionsSql($this->filters->filter(function ($entry) {
            return $entry['type'] === 'having';
        })->map(function ($entry) {
            return $entry['filter'];
        }));

        if (count($having_clause)) {
            $query .= sprintf(" HAVING %s", implode(' AND ', $having_clause));
        }

        // add order by if specified
        $sort_by = $this->getSortBy();

        if (!empty($sort_by)) {
            $query .= sprintf(' ORDER BY %s', $sort_by);
        }

        // the sql without limits will be needed for bulk actions
        $this->sql_without_limits = $query;

        // apply limits to the sql and return it
        return $this->sql_without_limits . sprintf(' LIMIT %d, %d', $this->offset, $this->results_per_page);
    }

    /**
     * Return all conditions that will be applied to query
     *
     * @param \TsfCorp\Lister\Filters\ListerFilter[]|\Illuminate\Support\Collection $filters
     * @return array
     */
    private function buildConditionsSql(Collection $filters)
    {
        $wheres = [];

        foreach ($filters as $filter) {
            if ($filter->getType() === ListerFilter::TYPE_RAW) {
                $filter->setActive(true);
                $wheres[] = $filter->getRawQuery();
            } else {
                $filter->setSearchKeyword($this->request->input($filter->getInputName()));

                $searched_keyword = $filter->getSearchKeyword();

                if (!empty($searched_keyword) || is_numeric($searched_keyword)) {
                    $filter->setActive(true);

                    // clean searched keyword
                    if (is_array($searched_keyword)) {
                        $searched_keyword = array_map(function ($item) {
                            return addslashes($item);
                        }, $searched_keyword);
                    } else {
                        $searched_keyword = addslashes($searched_keyword);
                    }

                    // build where clause
                    if (!empty($filter->getRawQuery())) {
                        if (is_array($searched_keyword)) {
                            $wheres[] = str_replace('{' . $filter->getInputName() . '}', "'" . implode("', '", $searched_keyword) . "'", $filter->getRawQuery());
                        } else {
                            $wheres[] = str_replace('{' . $filter->getInputName() . '}', addslashes($searched_keyword), $filter->getRawQuery());
                        }
                    } else if ($filter->getSearchOperator() == "IN" || is_array($searched_keyword)) {
                        if (!is_array($searched_keyword)) {
                            $searched_keyword = [$searched_keyword];
                        }

                        $wheres[] = sprintf("%s IN ('%s')", $filter->getDbColumn(), implode("', '", $searched_keyword));
                    } else if (strtoupper($filter->getSearchOperator()) == "LIKE") {
                        $wheres[] = sprintf("%s %s '%%%s%%'", $filter->getDbColumn(), $filter->getSearchOperator(), $searched_keyword);
                    } else {
                        if (!is_numeric($searched_keyword))
                            $format = "%s %s '%s'";
                        else
                            $format = "%s %s %s";

                        $wheres[] = sprintf($format, $filter->getDbColumn(), $filter->getSearchOperator(), $searched_keyword);
                    }
                }
                elseif($default_raw_query = $filter->getDefaultRawQuery()) {
                    $wheres[] = $default_raw_query;
                }
            }
        }

        return $wheres;
    }

    private function getSortBy(): string
    {
        if (empty($this->query_settings['sortables'])) {
            return '';
        }

        if ($this->request->has('sortf')) {
            $sort_field = $this->request->input('sortf');
        } else if (isset($this->query_settings['sortables'])) {
            $sort_field = '';

            foreach ($this->query_settings['sortables'] as $field => $direction) {
                if (in_array($direction, ['asc', 'desc'])) {
                    $sort_field = $field;
                    break;
                }
            }
        }

        if (!$sort_field) {
            return '';
        }

        if (!isset($this->query_settings['sortables'][$sort_field])) {
            return '';
        }

        $sort_direction = $this->request->input('sortd', $this->query_settings['sortables'][$sort_field] ?? 'asc');

        if (!empty($this->query_settings['unique_sort_column'])) {
            return sprintf("%s %s, %s %s", $sort_field, $sort_direction, $this->query_settings['unique_sort_column'], $sort_direction);
        }

        return sprintf("%s %s", $sort_field, $sort_direction);
    }

    private function forgetFilters(): void
    {
        Session::forget("filters.{$this->request->path()}");
    }

    private function fetchTotal(): int
    {
        $rows_query = $this->getUnlimitedSQLQuery();
        $sort_by = $this->getSortBy();

        if ($sort_by) {
            $rows_query = trim(str_replace(sprintf("ORDER BY %s", $sort_by), "", $rows_query));

            $remove_empty_filter = "WHERE (1)";
            if (substr($rows_query, -strlen($remove_empty_filter)) === $remove_empty_filter) {
                $rows_query = trim(str_replace($remove_empty_filter, "", $rows_query));
            }
        }

        $result = $this->db->select(sprintf("SELECT COUNT(*) as total FROM (%s) as total_count_table", $rows_query));

        if (empty($result) || !is_array($result)) {
            return 0;
        }

        if (count($result) == 1) {
            return (int)$result[0]->total;
        }

        // this is when a group by is applied to main query
        return count($result);
    }

    public function getResultsPerPage(): int
    {
        return $this->results_per_page;
    }

    public function setResultsPerPage(int $results_per_page)
    {
        $this->results_per_page = $results_per_page;
        $this->offset = $this->computeOffset();
    }

    public function getResults(): ?LengthAwarePaginator
    {
        return $this->results;
    }

    public function isFiltered(): bool
    {
        return (bool)$this->getActiveFilters()->count();
    }

    public function getUnlimitedSQLQuery(): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->sql_without_limits));
    }

    public function sortLink(string $sortf): string
    {
        $default_sortf = "";
        $default_sortd = "";

        if (isset($this->query_settings['sortables'])) {
            foreach ($this->query_settings['sortables'] as $field => $direction) {
                if (in_array($direction, ['asc', 'desc'])) {
                    $default_sortf = $field;
                    $default_sortd = $direction;
                    break;
                }
            }
        }

        $current_sortf = $this->request->input('sortf', $default_sortf);
        $current_sortd = $this->request->input('sortd', $default_sortd);

        if ($current_sortf == $sortf) {
            $sortd = $current_sortd == 'asc' ? 'desc' : 'asc';
        } else {
            $sortd = "desc";
        }

        $query_string_array = $this->request->all();

        $query_string_array['sortf'] = $sortf;
        $query_string_array['sortd'] = $sortd;

        $parts = [];

        foreach ($query_string_array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = "{$key}[]={$item}";
                }
            } elseif (strlen($value)) {
                $parts[] = "{$key}={$value}";
            }
        }

        $normalized_query_string = $this->request->normalizeQueryString(implode('&', $parts));

        return "{$this->request->url()}?{$normalized_query_string}";
    }

    public function sortDir(string $sortf): string
    {
        // currently sorting by field ...
        $current_sortf = $this->request->input('sortf');

        // ... asc or desc
        $current_sortd = $this->request->input('sortd');

        $sort_dir = '';

        if ((empty($current_sortf) || empty($current_sortd)) && isset($this->query_settings['sortables']) && !empty($this->query_settings['sortables'][$sortf])) {
            // $sortf is the default sorting field
            $sort_dir = $this->query_settings['sortables'][$sortf];
        } elseif ($current_sortf == $sortf) {
            // $sortf is the current one
            $sort_dir = $current_sortd;
        }

        // reverse $sort_dir for display
        if ($sort_dir == 'asc') {
            return config('lister.css_clas_sort_asc', 'sort-desc active');
        } elseif ($sort_dir == 'desc') {
            return config('lister.css_clas_sort_desc', 'sort-asc active');
        } else {
            return '';
        }
    }

    public function cleanQueryString(): string
    {
        $query_string_array = $this->request->all();
        $clean_query_string_array = [];
        $needs_redirect = false;

        foreach ($query_string_array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (strlen((string)$item) == 0) {
                        $needs_redirect = true;
                    } else {
                        $clean_query_string_array[] = "{$key}[]=" . urlencode((string)$item);
                    }
                }
            } else {
                if (strlen((string)$value) == 0) {
                    $needs_redirect = true;
                } else {
                    $clean_query_string_array[] = "{$key}=" . urlencode((string)$value);
                }
            }
        }

        $normalized_query_string = $this->request->normalizeQueryString(implode('&', $clean_query_string_array));

        if ($needs_redirect) {
            return "{$this->request->url()}?{$normalized_query_string}";
        }

        return '';
    }

    public function rememberFilters(): string|bool
    {
        $should_remember_filters = true;

        if($this->request->has('remember_filters') && !$this->request->input('remember_filters')) {
            $should_remember_filters = false;
        }

        if(!$should_remember_filters) {
            return false;
        }

        $uri = $this->request->path();

        $remembered = Session::get("filters.{$uri}");
        $input_query = $this->request->all();

        if ((!!$remembered && !count($input_query)) || $this->request->exists('reset')) {
            $this->forgetFilters();

            if ($this->request->exists('reset')) {
                return $uri;
            } else {
                return $remembered;
            }
        }

        $clean_query_string_array = [];

        if (count($input_query)) {
            foreach ($input_query as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $clean_query_string_array[] = "{$key}[]={$item}";
                    }
                } elseif (strlen((string)$value)) {
                    $clean_query_string_array[] = "{$key}={$value}";
                }
            }

            $query_strings = $this->request->normalizeQueryString(implode('&', $clean_query_string_array));

            $query_strings = !empty($query_strings) ? "?{$query_strings}" : $query_strings;

            Session::put("filters.{$uri}", $uri . $query_strings);
        }

        return false;
    }

    public function getRedirectUrl(): ?string
    {
        if ($remembered = $this->rememberFilters()) {
            return $remembered;
        }

        if ($clean_query_string = $this->cleanQueryString()) {
            return $clean_query_string;
        }

        return null;
    }

    public function getResultIndex(int $index = 0): int
    {
        return max($index, 0) + 1 + $this->getResultsPerPage() * ($this->current_page - 1);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \TsfCorp\Lister\Filters\ListerFilter>
     */
    public function getFilters(): Collection
    {
        return $this->filters->map(fn($entry) => $entry['filter']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \TsfCorp\Lister\Filters\ListerFilter>
     */
    public function getActiveFilters(): Collection
    {
        return $this->getFilters()->filter(fn(ListerFilter $filter) => $filter->isActive());
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            $object = [$this->default(), $name];
        } else {
            $object = [$this, $name];
        }

        return call_user_func_array($object, $arguments);
    }
}
