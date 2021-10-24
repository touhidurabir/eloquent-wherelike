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

    /**
     * @test
     */
    public function it_can_search() {

        Profile::create(['first_name' => 'First', 'last_name' => 'last']);

        $records = Profile::whereLike(['first_name', 'last_name'], 'first')->get();
        $this->assertCount(1, $records);

        $records = Profile::whereLike(['first_name', 'last_name'], 'filgdflgdf')->get();
        $this->assertCount(0, $records);
    }
    

    /**
     * @test
     */
    public function it_can_search_with_relations() {

        $user = User::create(['email' => 'user1@test.com', 'password' => '123456']);
        $user->profile()->create(['first_name' => 'User1', 'last_name' => 'user']);

        $user = User::create(['email' => 'user2@test.com', 'password' => '123456']);
        $user->profile()->create(['first_name' => 'User2', 'last_name' => 'user']);

        $records = User::whereLike(['email', '.profile[first_name, last_name]'], 'user1')->get();
        $this->assertCount(1, $records);

        $records = User::whereLike(['email', '.profile[first_name, last_name]'], 'user')->get();
        $this->assertCount(2, $records);

        $records = User::whereLike(['email', '.profile[first_name, last_name]'], 'gibrish')->get();
        $this->assertCount(0, $records);
    }

}