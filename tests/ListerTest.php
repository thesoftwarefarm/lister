<?php

namespace TsfCorp\Lister\Test;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use TsfCorp\Lister\Facades\ListerFilter;
use TsfCorp\Lister\Lister;
use TsfCorp\Lister\Test\Models\Role;
use TsfCorp\Lister\Test\Models\User;

class ListerTest extends TestBootstrap
{
    /**
     * @test
     * @throws \ErrorException
     */
    public function it_build_a_lister_based_on_query_settings()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults() instanceof LengthAwarePaginator);
        $this->assertEquals(User::count(), $listing->getResults()->total());
        $this->assertEquals(10, $listing->getResults()->count());
    }

    /**
     * Test pagination is applied for custom rpp - results per page
     * @test
     */
    public function it_returns_total_records_for_paginated_results()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $request = new Request([], [], ['rpp' => 3]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults() instanceof LengthAwarePaginator);
        $this->assertEquals(3, $listing->getResults()->count());
        $this->assertEquals(User::count(), $listing->getResults()->total());
    }

    /**
     * @test
     * @throws \ErrorException
     */
    public function it_sets_current_page_based_on_request_data()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users WHERE {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $request = new Request([], [], ['page' => 3]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->currentPage() == 3);
        $this->assertFalse($listing->isFiltered());
    }

    /**
     * Email filter is applied and filtered based on LIKE operator
     *
     * @test
     * @throws \ErrorException
     */
    public function it_applies_filters_with_like_operator()
    {
        User::create([
            'email' => "test123@mail.com",
            'name' => "User 1",
            'password' => "123456",
        ]);

        User::create([
            'email' => "test123@test.com",
            'name' => "User 2",
            'password' => "123456",
        ]);

        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $email_filter = ListerFilter::textfield("filter_email", "Email")
            ->setDbColumn("email")
            ->setSearchOperator("LIKE");

        $filter_email = 'test123';
        $request = new Request([], [], ['filter_email' => $filter_email]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($email_filter);

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 2);
        $this->assertTrue($lister->getResults()->contains('email', 'test123@mail.com'));
        $this->assertTrue($lister->isFiltered());

        $active_filters = $lister->getActiveFilters();
        $this->assertCount(1, $active_filters);
        $this->assertEquals('test123', $active_filters->first()->getSearchKeyword());
    }

    /**
     * Email filter is applied and filtered based on strict (=) operator
     *
     * @test
     * @throws \ErrorException
     */
    public function it_applies_filters_with_strict_operator()
    {
        User::create([
            'email' => "test123@mail.com",
            'name' => "User 1",
            'password' => "123456",
        ]);

        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $email_filter = ListerFilter::textfield("filter_email", "Email")
            ->setDbColumn("email")
            ->setSearchOperator("=");

        $filter_email = 'test123@mail.com';
        $request = new Request([], [], ['filter_email' => $filter_email]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($email_filter);

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 1);

        $user = $lister->getResults()->first();

        $this->assertTrue($user->email == $filter_email);
        $this->assertCount(1, $lister->getActiveFilters());
        $this->assertTrue($lister->isFiltered());
    }

    /**
     * Filter is applied with raw query
     *
     * @test
     * @throws \ErrorException
     */
    public function it_applies_filters_with_raw_query()
    {
        User::create([
            'email' => "test1@mail.com",
            'name' => "test1",
            'password' => "123456",
        ]);

        User::create([
            'email' => "test2@mail.com",
            'name' => "test2",
            'password' => "123456",
        ]);

        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $filter = ListerFilter::textfield("keyword", "Email")
            ->setRawQuery("email LIKE '%{keyword}%' OR name LIKE '%{keyword}%'");

        $request = new Request([], [], ['keyword' => 'test']);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($filter);

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 2);
        $this->assertCount(1, $lister->getActiveFilters());
    }

    /**
     * Filter is applied with raw query with search keyword as array
     *
     * @test
     * @throws \ErrorException
     */
    public function it_applies_filters_with_raw_query_for_input_array()
    {
        User::create([
            'email' => "test1@mail.com",
            'name' => "test1",
            'password' => "123456",
        ]);

        User::create([
            'email' => "test2@mail.com",
            'name' => "test2",
            'password' => "123456",
        ]);

        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $filter = ListerFilter::textfield("keyword", "Email")
            ->setRawQuery("email IN ({keyword})");

        $request = new Request([], [], ['keyword' => ['test1@mail.com', 'test2@mail.com']]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($filter);

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 2);
        $this->assertCount(1, $lister->getActiveFilters());
    }

    /**
     * Raw filter is applied
     *
     * @test
     * @throws \ErrorException
     */
    public function it_applies_raw_filters()
    {
        User::create([
            'email' => "test1@mail.com",
            'name' => "test1",
            'password' => "123456",
        ]);

        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter(ListerFilter::raw("email = 'test1@mail.com'")->noRender());

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 1);
        $this->assertCount(1, $lister->getActiveFilters());
    }

    /**
     * @test
     * @throws \ErrorException
     */
    public function it_doesnt_apply_filter_for_empty_request()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users WHERE name <> '' and {filters}",

            'filters' => [
                "email LIKE '{filter_email}'",
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $email_filter = ListerFilter::textfield("filter_email", "Email")
            ->setDbColumn("email")
            ->setSearchOperator("LIKE");

        $request = new Request([], [], ['filter_email' => '']);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($email_filter);

        $listing = $lister->get();

        $this->assertEmpty($lister->getActiveFilters());
        $this->assertFalse($listing->isFiltered());
    }

    public function testWhereFilterMultipleLines()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users WHERE 
                name <> '' and
                email <> '' and
                {filters}",

            'filters' => [
                "email LIKE '{filter_email}'",
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $request = new Request([], [], ['filter_email' => '']);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertFalse($listing->isFiltered());
    }

    /**
     * Check filters are applied for numberic filters, including zero (0) number
     *
     * @test
     * @throws \ErrorException
     */
    public function it_filters_for_zero_number()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $numeric_filter = ListerFilter::textfield("filter_id", "ID")->setDbColumn("id");

        $request = new Request([], [], ['filter_id' => 0]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($numeric_filter);

        $listing = $lister->get();

        $this->assertTrue($listing->isFiltered());
    }

    /**
     * Filters work for input type array
     *
     * @test
     * @throws \ErrorException
     */
    public function filters_are_applied_for_input_array()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'filters' => [
                'id IN ({filter_id})',
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $numeric_filter = ListerFilter::textfield("filter_id", "ID")
            ->setDbColumn("id")
            ->setSearchOperator("IN");

        $request = new Request([], [], ['filter_id' => [1, 2]]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addFilter($numeric_filter);

        $listing = $lister->get();

        $this->assertTrue($listing->getResults()->total() == 2);
        $this->assertTrue($listing->isFiltered());

        $active_filters = $lister->getActiveFilters();
        $this->assertCount(1, $active_filters);
        $this->assertEquals([1, 2], $active_filters->first()->getSearchKeyword());
    }

    public function testDifferentConnectionByName()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $listing = $lister->setConnection('other_conn')->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->count() > 1);
    }

    public function testDifferentConnectionByObject()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $listing = $lister->setConnection(DB::connection('other_conn'))->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->count() > 1);
    }

    public function testRecordsAreHydratedIfModelSet()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'sortables' => [
                'name' => 'asc',
            ],

            'model' => User::class,
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        foreach ($listing->results as $result) {
            $this->assertInstanceOf(User::class, $result);
        }
    }

    /**
     * Total number of rows must work fine for groupping
     *
     * @test
     */
    public function for_group_by_it_return_correct_total()
    {
        $query_settings = [
            'fields' => "r.*, COUNT(r.id)",

            'body' => "FROM roles r
            INNER JOIN roles_2_users r2u on r.id = r2u.role_id
            GROUP BY r.id
            {filters}",

            'sortables' => [
                'name' => 'asc',
            ],

            'model' => Role::class,
        ];

        $request = new Request([], [], ['rpp' => 3]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $assigned_roles_count = DB::table("roles_2_users")->groupBy("role_id")->get()->count();

        $this->assertEquals($assigned_roles_count, $listing->getResults()->total());
        $this->assertEquals(3, $listing->getResults()->count()); // per page
    }

    /**
     * Currently, when fetching total records for query, Lister is removing all select fields and replacing them with COUNT(*) as total
     *
     * This leads to query errors when joining tables which have same column names and current sort key is one of them
     *
     * @test
     * @throws \ErrorException
     */
    public function it_not_throws_exception_when_fetching_total_records_caused_by_ambiguous_column_name_in_order_by_clause()
    {
        $query_settings = [
            'fields' => "u.*",

            'body' => "FROM users u
                       JOIN roles_2_users r2u on r2u.user_id = u.id
                       JOIN roles r on r2u.role_id = r.id
                       WHERE {filters}",

            'sortables' => [
                'id' => 'desc',
            ],
        ];

        $request = new Request([], [], ['page' => 3]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)->get();

        $this->assertTrue(true);
    }

    /**
     * Total number of rows must work fine for groupping
     *
     * @test
     */
    public function filter_with_having_for_group_by()
    {
        $user = User::create([
            'email' => "test2@mail.com",
            'name' => "test2",
            'password' => "123456",
        ]);

        $role1 = Role::create([
            'name' => "role1",
        ]);

        $role2 = Role::create([
            'name' => "role2",
        ]);

        $role3 = Role::create([
            'name' => "role3",
        ]);

        DB::table('roles_2_users')->insert([
            ['user_id' => $user->id, 'role_id' => $role1->id],
            ['user_id' => $user->id, 'role_id' => $role2->id],
            ['user_id' => $user->id, 'role_id' => $role3->id],
        ]);

        $query_settings = [
            'fields' => "u.*, GROUP_CONCAT(r.name) as role_name",

            'body' => "FROM users u
                        LEFT JOIN roles_2_users r2u on u.id = r2u.user_id
                        LEFT JOIN roles r on r.id = r2u.role_id
                        {filters}
                        GROUP BY u.id
            {filters}",

            'sortables' => [
            ],

            'model' => User::class,
        ];

        $role_filter = ListerFilter::textfield("role_name", "Role name")
            ->setSearchOperator("LIKE");

        $request = new Request([], [], ['role_name' => "role2"]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $lister->make($query_settings)
            ->addHavingFilter($role_filter);

        $listing = $lister->get();
        $results = $listing->getResults();

        $this->assertEquals(1, $results->total());
        $this->assertEquals($user->name, $results->first()->name);
        $this->assertStringContainsString('role2', $results->first()->role_name);
    }
}
