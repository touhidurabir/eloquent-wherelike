<?php

namespace Touhidurabir\EloquentWherelike\Tests;

use PHPUnit\Framework\Attributes\Test;
use Orchestra\Testbench\TestCase;
use Touhidurabir\EloquentWherelike\Tests\App\User;
use Touhidurabir\EloquentWherelike\Tests\App\Profile;
use Touhidurabir\EloquentWherelike\Tests\App\WithTrashedCountingBuilder;
use Touhidurabir\EloquentWherelike\Tests\Traits\LaravelTestBootstrapping;

class WhereLikeTest extends TestCase
{
    use LaravelTestBootstrapping;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {

        parent::setUp();

        WithTrashedCountingBuilder::$withTrashedCalls = 0;
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {

        $this->loadMigrationsFrom(__DIR__ . '/App/database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback', ['--database' => 'testbench'])->run();
        });
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_search()
    {

        Profile::create(['first_name' => 'First', 'last_name' => 'last']);

        $records = Profile::whereLike(['first_name', 'last_name'], 'first')->get();
        $this->assertCount(1, $records);

        $records = Profile::whereLike(['first_name', 'last_name'], 'filgdflgdf')->get();
        $this->assertCount(0, $records);
    }


    /**
     * @test
     */
    #[Test]
    public function it_can_search_with_relations()
    {

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


    /**
     * @test
     */
    #[Test]
    public function it_ignores_empty_terms_caused_by_repeated_whitespace()
    {

        Profile::create(['first_name' => 'First', 'last_name' => 'last']);
        Profile::create(['first_name' => 'Other', 'last_name' => 'Person']);

        $this->assertCount(1, Profile::whereLike(['first_name', 'last_name'], 'first  last')->get());
        $this->assertCount(1, Profile::whereLike(['first_name', 'last_name'], ' first ')->get());
        $this->assertCount(2, Profile::whereLike(['first_name', 'last_name'], 'first person')->get());
        $this->assertCount(0, Profile::whereLike(['first_name', 'last_name'], 'nothing  matches')->get());
    }


    /**
     * @test
     */
    #[Test]
    public function it_can_include_soft_deleted_relation_records()
    {

        $user = User::create(['email' => 'user1@test.com', 'password' => '123456']);
        $user->profile()->create(['first_name' => 'Trashed', 'last_name' => 'user'])->delete();

        $this->assertCount(0, User::whereLike(['.profile[first_name, last_name]'], 'trashed')->get());
        $this->assertCount(1, User::whereLike(['.profile[first_name, last_name]'], 'trashed', true)->get());
    }


    /**
     * @test
     */
    #[Test]
    public function it_calls_with_trashed_once_per_relation_not_once_per_column()
    {

        $user = User::create(['email' => 'user1@test.com', 'password' => '123456']);
        $user->trackedProfile()->create(['first_name' => 'Trashed', 'last_name' => 'user'])->delete();

        $records = User::whereLike(['.trackedProfile[first_name, last_name]'], 'trashed', true)->get();

        $this->assertCount(1, $records);
        $this->assertSame(1, WithTrashedCountingBuilder::$withTrashedCalls);
    }

}
