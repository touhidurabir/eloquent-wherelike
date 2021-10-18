<?php

namespace Touhidurabir\EloquentWherelike\Tests;

use Orchestra\Testbench\TestCase;
use Touhidurabir\EloquentWherelike\Tests\App\User;
use Touhidurabir\EloquentWherelike\Tests\App\Profile;
use Touhidurabir\EloquentWherelike\Tests\Traits\LaravelTestBootstrapping;

class WhereLikeTest extends TestCase {

    use LaravelTestBootstrapping;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations() {

        $this->loadMigrationsFrom(__DIR__ . '/App/database/migrations');
        
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'testbench'])->run();
        });
    }

}