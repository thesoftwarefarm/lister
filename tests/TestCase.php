<?php

namespace TsfCorp\Lister\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;
use TsfCorp\Lister\Tests\Models\Role;
use TsfCorp\Lister\Tests\Models\User;

class TestCase extends Orchestra
{
    use WithFaker;

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
            $role = Role::forceCreate([
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
            $user = User::forceCreate([
                'email' => $this->faker->email,
                'name' => $this->faker->name,
                'password' => $this->faker->password,
            ]);

            $user->roles()->attach($roles->random(3)->pluck('id'));
        }
    }

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
