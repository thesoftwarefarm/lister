<?php

namespace TsfCorp\Lister;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;

class Lister
{
    public $results;
    private $query_settings;
    private $results_per_page = 10;
    private $current_page = 1;
    private $offset = 0;
    private $filters_applied = false;
    private $sql_without_limits;
    private $request;
    private $db;


    /**
     * Lister constructor.
     *
     * @param Request $request
     * @param Connection $db
     */
    public function __construct(Request $request, Connection $db)
    {
        $this->request = $request;
        $this->db = $db;

        $this->current_page = $this->request->get('page', $this->current_page);
        $this->results_per_page = $this->request->get('rpp') ?? config('lister.results_per_page');
        $this->offset = $this->computeOffset();
    }

    public function setConnection(Connection $db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Returns the offset needed for executing the query,
     * given the current 'results per page'/'current page' configuration.
     *
     * @return int|mixed
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
     *
     * @return $this
     */
    public function make($query_settings)
    {
        $this->query_settings = $query_settings;

        return $this;
    }

    /**
     * Fetches records and total figure and creates a paginator instance.
     * Returns a Lister instance where the 'results' member variable is populated with paginated results.
     * By exposing the 'results' member variable, the results can be intercepted
     * and altered in the controller before being sent to the view.
     *
     * @return $this
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
     */
    private function fetchRecords()
    {
        return $this->db->select($this->buildQuery());
    }

    /**
     * Builds and returns an SQL query that combines filters, sorting rules and pagination settings.
     *
     * @return string
     */
    private function buildQuery()
    {
        // add where clause to query body
        $conditions = $this->getWhereClause();

        $query = $this->query_settings['body'];

        if (count($conditions)) {
            $query = str_replace('{filters}', ' WHERE ' . implode(' AND ', $this->getWhereClause()), $query);
        } else {
            $query = str_replace('{filters}', '', $query);
        }

        // build query with fields and body
        $query = sprintf('SELECT SQL_CALC_FOUND_ROWS %s %s', $this->query_settings['fields'], $query);

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
     * @return array
     */
    private function getWhereClause()
    {
        $filters = [];

        foreach ($this->query_settings['filters'] as $filter) {
            // add binding with value if we have something in {}
            preg_match('/\{(.*?)\}/', $filter, $matches);

            if (count($matches) && isset($matches[1])) {
                $filter_name = $matches[1];
                $filter_value = $this->request->get($filter_name);

                if (!empty($filter_value) || is_numeric($filter_value)) {
                    $this->filters_applied = true;

                    if (is_array($filter_value)) {
                        $filter_value = array_map(function ($item) {
                            return addslashes($item);
                        }, $filter_value);

                        $filters[] = str_replace('{' . $filter_name . '}', "'" . implode("', '", $filter_value) . "'", $filter);
                    } else {
                        $filters[] = str_replace('{' . $filter_name . '}', addslashes($filter_value), $filter);
                    }
                }
            } else {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Get sort field and direction
     *
     * @return int|mixed|string
     */
    private function getSortBy()
    {
        if ($this->request->has('sortf')) {
            $sort_field = $this->request->get('sortf');
        } else {
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
        } catch (\ErrorException $e) {
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
        $total = $this->db->select("SELECT FOUND_ROWS() as counter");

        return isset($total[0]->counter) ? $total[0]->counter : 0;
    }

    /**
     * A getter for the number of results per page.
     *
     * @return int
     */
    public function getResultsPerPage()
    {
        return $this->results_per_page;
    }

    /**
     * A setter for the number of results per page.
     *
     * @param $results_per_page
     * @return void
     */
    public function setResultsPerPage($results_per_page)
    {
        $this->results_per_page = (int)$results_per_page;
        $this->offset = $this->computeOffset();
    }

    /**
     * @return LengthAwarePaginator
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Whether there are any filters applied to the current listing.
     *
     * @return bool
     */
    public function isFiltered()
    {
        return $this->filters_applied;
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
     *
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
     *
     * @return string
     */
    public function sortLink($sortf = "")
    {
        $default_sortf = "";
        $default_sortd = "";

        foreach ($this->query_settings['sortables'] as $sortfield => $sortdir) {
            if (in_array($sortdir, array('asc', 'desc'))) {
                $default_sortf = $sortfield;
                $default_sortd = $sortdir;
                break;
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
            $parts[] = $key . '=' . $value;
        }

        $normalized_query_string = $this->request->normalizeQueryString(implode('&', $parts));

        return $this->request->url() . '?' . $normalized_query_string;
    }

    /**
     * Given a sorting field, this returns the CSS classes to be applied to the corresponding sorting button.
     *
     * @param string $sortf
     *
     * @return string
     */
    public function sortDir($sortf = "")
    {
        // currently sorting by field ...
        $current_sortf = $this->request->get('sortf');

        // ... asc or desc
        $current_sortd = $this->request->get('sortd');

        $sort_dir = "";

        if ((empty($current_sortf) || empty($current_sortd)) && !empty($this->query_settings['sortables'][$sortf])) {
            // $sortf is the default sorting field
            $sort_dir = $this->query_settings['sortables'][$sortf];
        } elseif ($current_sortf == $sortf) {
            // $sortf is the current one
            $sort_dir = $current_sortd;
        }

        // reverse $sort_dir for display
        if ($sort_dir == 'asc') {
            return "sort-desc active";
        } elseif ($sort_dir == 'desc') {
            return "sort-asc active";
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
            if (strlen($value) == 0) {
                $needs_redirect = true;
            } else {
                $clean_query_string_array[] = $key . '=' . $value;
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
                if (strlen($value)) {
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
        if($remembered = $this->rememberFilters()) return $remembered;

        if($clean_query_string = $this->cleanQueryString()) return $clean_query_string;

        return NULL;
    }
}
