<?php

namespace TsfCorp\Lister;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use TsfCorp\Lister\Filters\ListerFilter;

class Lister
{
    /**
     * @var \Illuminate\Pagination\LengthAwarePaginator
     */
    public $results;
    /**
     * @var array
     */
    private $query_settings;
    /**
     * @var int
     */
    private $results_per_page = 10;
    /**
     * @var int
     */
    private $current_page = 1;
    /**
     * @var int
     */
    private $offset = 0;
    /**
     * @var string
     */
    private $sql_without_limits;
    /**
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * @var \Illuminate\Database\Connection
     */
    private $db;
    /**
     * @var \TsfCorp\Lister\Filters\ListerFilter[]|\Illuminate\Support\Collection
     */
    private $filters = null;

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Connection $db
     */
    public function __construct(Request $request, Connection $db)
    {
        $this->request = $request;
        $this->db = $db;
        $this->filters = new Collection([]);

        $this->current_page = $this->request->get('page', $this->current_page);
        $this->results_per_page = $this->request->get('rpp') ?? config('lister.results_per_page', 20);
        $this->offset = $this->computeOffset();
    }

    /**
     * @param string|\Illuminate\Database\Connection $connection
     * @return \TsfCorp\Lister\Lister
     */
    public function setConnection($connection)
    {
        if (is_string($connection)) {
            $this->db = DB::connection($connection);
        } else if ($connection instanceof Connection) {
            $this->db = $connection;
        }

        return $this;
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->db;
    }

    /**
     * Returns the offset needed for executing the query,
     * given the current 'results per page'/'current page' configuration.
     *
     * @return int
     */
    private function computeOffset()
    {
        if ($this->current_page == 1) {
            return 0;
        }

        return $this->results_per_page * $this->current_page - $this->results_per_page;
    }

    /**
     * Returns a Lister instance with query settings applied.
     *
     * @param $query_settings
     * @return \TsfCorp\Lister\Lister
     */
    public function make($query_settings): Lister
    {
        $this->query_settings = $query_settings;

        return $this;
    }

    /**
     * Add a new filter
     *
     * @param \TsfCorp\Lister\Filters\ListerFilter $filter
     * @return \TsfCorp\Lister\Lister
     * @throws Exception
     */
    public function addFilter(ListerFilter $filter): Lister
    {
        $filter->validate();

        $this->filters->push([
            'type' => 'where',
            'filter' => $filter,
        ]);

        return $this;
    }

    /**
     * Add a new filter for HAVING
     *
     * @param \TsfCorp\Lister\Filters\ListerFilter $filter
     * @return \TsfCorp\Lister\Lister
     */
    public function addHavingFilter(ListerFilter $filter): Lister
    {
        $this->filters->push([
            'type' => 'having',
            'filter' => $filter,
        ]);

        return $this;
    }

    /**
     * Fetches records and total figure and creates a paginator instance.
     * Returns a Lister instance where the 'results' member variable is populated with paginated results.
     * By exposing the 'results' member variable, the results can be intercepted
     * and altered in the controller before being sent to the view.
     *
     * @return \TsfCorp\Lister\Lister
     * @throws \ErrorException
     */
    public function get()
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

    /**
     * Executes the query and returns a results array.
     *
     * @return array
     * @throws \ErrorException
     */
    private function fetchRecords()
    {
        $results = $this->db->select($this->buildQuery());

        $model = !empty($this->query_settings['model']) ? $this->query_settings['model'] : null;

        if ($model && class_exists($model)) {
            return $model::hydrate($results);
        }

        return $results;
    }

    /**
     * Builds and returns an SQL query that combines filters, sorting rules and pagination settings.
     *
     * @return string
     * @throws \ErrorException
     */
    private function buildQuery()
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
                $searched_keyword = $this->request->get($filter->getInputName());
                $filter->setSearchKeyword($searched_keyword);

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
            }
        }

        return $wheres;
    }

    /**
     * Get sort field and direction
     *
     * @return string
     * @throws Exception
     */
    private function getSortBy()
    {
        if ($this->request->has('sortf')) {
            $sort_field = $this->request->get('sortf');
        } else if (isset($this->query_settings['sortables'])) {
            $sort_field = '';

            foreach ($this->query_settings['sortables'] as $sortfield => $sort_direction) {
                if (in_array($sort_direction, array('asc', 'desc'))) {
                    $sort_field = $sortfield;
                    break;
                }
            }
        }

        // without a field, no need to check for sort direction
        if (empty($sort_field))
            return "";

        // when sorting by a field that doesn't exist, an exception is thrown;
        // if the filters are "remembered", the user can't recover by changing the URL;
        // to mitigate this, the exception is catched, and thrown again after clearing the filters.
        try {
            $sort_field .= " " . $this->request->get('sortd', $this->query_settings['sortables'][$sort_field]);
        } catch (Exception $e) {
            $this->forgetFilters();
            throw $e;
        }

        return $sort_field;
    }

    private function forgetFilters()
    {
        $uri = $this->request->path();

        Session::forget('filters.' . $uri);
    }

    /**
     * Returns the total (unfiltered) number of results.
     *
     * @return int
     */
    private function fetchTotal()
    {
        $rows_query = $this->getUnlimitedSQLQuery();
        $sort_by = $this->getSortBy();

        if (!empty($sort_by)) {
            $rows_query = trim(str_replace(sprintf("ORDER BY %s", $sort_by), "", $rows_query));

            $remove_empty_filter = "WHERE (1)";
            if (substr($rows_query, -strlen($remove_empty_filter)) === $remove_empty_filter) {
                $rows_query = trim(str_replace($remove_empty_filter, "", $rows_query));
            }
        }

        $result = $this->db->select(sprintf("SELECT COUNT(*) as total FROM (%s) as total_count_table", $rows_query));

        if (empty($result) || !is_array($result))
            return 0;

        if (count($result) == 1) {
            return $result[0]->total;
        } else {
            // this is when a group by is applied to main query
            return count($result);
        }
    }

    /**
     * @return int
     */
    public function getResultsPerPage()
    {
        return $this->results_per_page;
    }

    /**
     * @param $results_per_page
     * @return void
     */
    public function setResultsPerPage($results_per_page)
    {
        $this->results_per_page = (int)$results_per_page;
        $this->offset = $this->computeOffset();
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return bool
     */
    public function isFiltered()
    {
        return $this->getActiveFilters()->count() ? true : false;
    }

    /**
     * A getter for the SQL query without limits applied.
     *
     * @return string
     */
    public function getUnlimitedSQLQuery()
    {
        return $this->makeOneLiner($this->sql_without_limits);
    }

    /**
     * Given a string, returns a stripped version, where newlines and extra spaces are removed.
     *
     * @param string $string
     * @return string
     */
    private function makeOneLiner($string = "")
    {
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Given a sorting field, this returns an URL having this field as a sort parameter.
     *
     * @param string $sortf
     * @return string
     */
    public function sortLink($sortf = "")
    {
        $default_sortf = "";
        $default_sortd = "";

        if (isset($this->query_settings['sortables'])) {
            foreach ($this->query_settings['sortables'] as $sortfield => $sortdir) {
                if (in_array($sortdir, array('asc', 'desc'))) {
                    $default_sortf = $sortfield;
                    $default_sortd = $sortdir;
                    break;
                }
            }
        }

        $current_sortf = $this->request->get('sortf', $default_sortf);
        $current_sortd = $this->request->get('sortd', $default_sortd);

        if ($current_sortf == $sortf) {
            $sortd = $current_sortd == 'asc' ? 'desc' : 'asc';
        } else {
            $sortd = "desc";
        }

        $query_string_array = $this->request->all();

        $query_string_array['sortf'] = $sortf;
        $query_string_array['sortd'] = $sortd;

        $parts = array();

        foreach ($query_string_array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = $key . '[]=' . $item;
                }
            } elseif (strlen($value)) {
                $parts[] = $key . '=' . $value;
            }
        }

        $normalized_query_string = $this->request->normalizeQueryString(implode('&', $parts));

        return $this->request->url() . '?' . $normalized_query_string;
    }

    /**
     * Given a sorting field, this returns the CSS classes to be applied to the corresponding sorting button.
     *
     * @param string $sortf
     * @return string
     */
    public function sortDir($sortf = "")
    {
        // currently sorting by field ...
        $current_sortf = $this->request->get('sortf');

        // ... asc or desc
        $current_sortd = $this->request->get('sortd');

        $sort_dir = "";

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
            return "";
        }
    }

    /**
     * Removes empty query params from the query string.
     *
     * @return bool|string
     */
    public function cleanQueryString()
    {
        $query_string_array = $this->request->all();
        $clean_query_string_array = [];
        $needs_redirect = false;

        foreach ($query_string_array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (strlen($item) == 0) {
                        $needs_redirect = true;
                    } else {
                        $clean_query_string_array[] = $key . '[]=' . urlencode($item);
                    }
                }
            } else {
                if (strlen($value) == 0) {
                    $needs_redirect = true;
                } else {
                    $clean_query_string_array[] = $key . '=' . urlencode($value);
                }
            }
        }

        $normalized_query_string = $this->request->normalizeQueryString(implode('&', $clean_query_string_array));

        if ($needs_redirect) {
            return $this->request->url() . '?' . $normalized_query_string;
        } else {
            return false;
        }

    }

    /**
     * Housekeeping for URLs kept in session and for filters reset.
     *
     * @return bool|mixed|string
     */
    public function rememberFilters()
    {
        $uri = $this->request->path();

        $remembered = Session::get('filters.' . $uri);
        $input_query = $this->request->all();

        if ((!!$remembered && !count($input_query)) || $this->request->exists('reset')) {
            $this->forgetFilters();

            if ($this->request->exists('reset')) {
                return $uri;
            } else {
                return $remembered;
            }
        }

        $clean_query_string_array = array();

        if (count($input_query)) {
            foreach ($input_query as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $clean_query_string_array[] = $key . '[]=' . $item;
                    }
                } elseif (strlen($value)) {
                    $clean_query_string_array[] = $key . '=' . $value;
                }
            }

            $query_strings = $this->request->normalizeQueryString(implode('&', $clean_query_string_array));

            $query_strings = !empty($query_strings) ? '?' . $query_strings : $query_strings;

            Session::put('filters.' . $uri, $uri . $query_strings);
        }

        return false;
    }

    /**
     * Returns an URL to redirect to if either:
     *  - there is a remembered URL in session
     *  - query string clean up has been performed
     *
     * @return null|string
     */
    public function getRedirectUrl()
    {
        if ($remembered = $this->rememberFilters()) {
            return $remembered;
        }

        if ($clean_query_string = $this->cleanQueryString()) {
            return $clean_query_string;
        }

        return null;
    }

    /**
     * Bulid result index to display in listing screens
     *
     * @param int $index
     * @return mixed
     */
    public function getResultIndex($index = 0)
    {
        return max($index, 0) + 1 + $this->getResultsPerPage() * ($this->current_page - 1);
    }

    /**
     * Returns all defined filters for this instance
     *
     * @return Collection|ListerFilter[]
     */
    public function getFilters()
    {
        return $this->filters->map(function ($entry) {
            return $entry['filter'];
        });
    }

    /**
     * @return \TsfCorp\Lister\Filters\ListerFilter[]|\Illuminate\Support\Collection
     */
    public function getActiveFilters()
    {
        return $this->filters->map(function ($entry) {
            return $entry['filter'];
        })->filter(function ($filter) {
            /** @var ListerFilter $filter */
            return $filter->isActive();
        });
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
