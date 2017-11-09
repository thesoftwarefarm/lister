<?php

namespace TsfCorp\Lister\Test;

use Faker\Factory;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Orchestra\Testbench\TestCase;
use TsfCorp\Lister\Lister;
use TsfCorp\Lister\Test\Migrations\CreateTestingUsersTable;

class ListerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase()
    {
        (new CreateTestingUsersTable())->up();

        $faker = Factory::create();

        // create a specific user so we can filter
        User::create([
            'email' => "testme123@tsf.com",
            'name' => "Tester",
        ]);

        for ($i = 0; $i < 10; $i++) {
            User::create([
                'email' => $faker->email,
                'name' => $faker->name(),
            ]);
        }
    }

    public function testData()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'filters' => [
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $lister = new Lister($this->app->make(Request::class), $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults() instanceof LengthAwarePaginator);
        $this->assertTrue($listing->getResults()->total() == User::count());
        $this->assertTrue($listing->getResults()->count() == 10);
    }

    public function testCurrentPage()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'filters' => [
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $request = new Request([], [], ['page' => 3]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->currentPage() == 3);
    }

    public function testFilter()
    {
        $query_settings = [
            'fields' => "users.*",

            'body' => "FROM users {filters}",

            'filters' => [
                "email LIKE '{filter_email}'",
            ],

            'sortables' => [
                'name' => 'asc',
            ],
        ];

        $filter_email = 'testme123@tsf.com';
        $request = new Request([], [], ['filter_email' => $filter_email]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->total() == 1);

        $user = $lister->getResults()->first();
        $this->assertTrue($user->email == $filter_email);
    }

    public function testFilterArray()
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

        $request = new Request([], [], ['filter_id' => [1, 2]]);

        $lister = new Lister($request, $this->app->make(Connection::class));
        $listing = $lister->make($query_settings)->get();

        $this->assertTrue($listing->getResults()->total() == 2);
    }

    /**
     * Drop tables
     */
    protected function tearDown()
    {
        (new CreateTestingUsersTable())->down();

        parent::tearDown();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'testing',
            'username' => 'homestead',
            'password' => 'secret',
        ]);
    }
}