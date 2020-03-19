<?php

namespace TsfCorp\Lister\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;
use TsfCorp\Lister\Test\Models\Role;
use TsfCorp\Lister\Test\Models\User;

class TestBootstrap extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom([
            '--database' => 'testbench',
            '--path' => realpath(__DIR__ . '/migrations'),
        ]);

        $this->loadMigrationsFrom([
            '--database' => 'other_conn',
            '--path' => realpath(__DIR__ . '/migrations'),
        ]);

        $this->setUpDatabase();
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase()
    {
        // create roles
        $roles = collect([]);
        for ($i = 0; $i < 50; $i++) {
            $role = Role::create([
                'name' => $this->faker->name,
            ]);

            $roles->add($role);
        }

        // remove last 3 items. these won't be assigned to any user
        $roles->pop();
        $roles->pop();
        $roles->pop();

        for ($i = 0; $i < 50; $i++) {
            /** @var User $user */
            $user = User::create([
                'email' => $this->faker->email,
                'name' => $this->faker->name,
                'password' => $this->faker->password,
            ]);

            $user->roles()->attach($roles->random(3)->pluck('id'));
        }
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('lister.results_per_page', 10);
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.other_conn', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('view.paths', [
            __DIR__ . '/../views',
        ]);

        $app['view']->addNamespace('lister', __DIR__ . '/../views');

        View::addLocation(__DIR__.'/stubs');
    }

    protected function getPackageProviders($app)
    {
        return ['TsfCorp\Lister\ListerServiceProvider'];
    }
}