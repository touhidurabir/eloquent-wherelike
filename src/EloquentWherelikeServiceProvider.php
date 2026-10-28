<?php

namespace Touhidurabir\EloquentWherelike;

use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class EloquentWherelikeServiceProvider extends ServiceProvider
{
    /**
     * The escape character used when the LIKE wildcards are escaped.
     *
     * The backslash is avoided on purpose: MySQL and PostgreSQL disagree on
     * how to write it in an SQL string literal, while "!" needs no quoting.
     *
     * @var string
     */
    public const LIKE_ESCAPE_CHARACTER = '!';

    /**
     * Recursively resolve the chained relational dependency
     *
     * @param  object<\Illuminate\Database\Eloquent\Builder>   $builder
     * @param  mixed                                        $searchTerm
     * @param  array                                        $relations
     * @param  bool                                         $withTrash
     * @param  string                                       $relationalCondition
     *
     * @return Builder
     */
    public function resolveRelationDependency(
        Builder $builder,
        $searchTerm,
        array   $relations           = [],
        bool    $withTrash           = false,
        string  $relationalCondition = 'orWhereHas'
    ) {

        $self = $this;

        if (empty($relations)) {

            return $builder;
        }

        $relation = array_shift($relations);

        $relationParams  = Str::contains($relation, '[') && Str::contains($relation, ']')
                               ? explode(',', preg_replace('/(.*)\[(.*)\](.*)/sm', '\2', $relation))
                               : [];
        $relationName    = preg_replace('/\[[\s\S]+?\]/', '', $relation);

        return
            $builder->{$relationalCondition}($relationName, function ($query) use ($relationParams, $searchTerm, $relations, $withTrash, $self) {

                $query = $self->resolveRelationDependency($query, $searchTerm, $relations, $withTrash, 'whereHas');

                $method = 'where';

                if ($withTrash && ! empty($relationParams)) {

                    $query = $query->withTrashed();
                }

                foreach ($relationParams as $relParam) {

                    $self->addLikeConstraint($query, $method, trim($relParam), $searchTerm);

                    $method = 'orWhere';
                }
            });
    }


    /**
     * Add a single "column LIKE %term%" constraint to the query
     *
     * When the "escape_wildcards" config option is enabled, the LIKE wildcards
     * (% and _) in the search term are escaped so that they match literally.
     * The escaping uses an explicit ESCAPE clause, so that it works the same
     * on every database driver (SQLite has no default escape character).
     *
     * @param  object<\Illuminate\Database\Eloquent\Builder>   $query
     * @param  string                                       $method     where|orWhere
     * @param  string                                       $column
     * @param  mixed                                        $searchTerm
     *
     * @return Builder
     */
    public function addLikeConstraint(Builder $query, string $method, string $column, $searchTerm)
    {

        $operator = config('eloquent-wherelike.operator') ?? 'LIKE';

        if (! config('eloquent-wherelike.escape_wildcards', false)) {

            return $query->{$method}($column, $operator, "%{$searchTerm}%");
        }

        if (! preg_match('/^[a-z ]+$/i', $operator)) {

            throw new InvalidArgumentException(
                "The eloquent-wherelike.operator [{$operator}] is not valid when escape_wildcards is enabled."
            );
        }

        $baseQuery = $query->getQuery();

        return $query->{$method === 'orWhere' ? 'orWhereRaw' : 'whereRaw'}(
            $baseQuery->getGrammar()->wrap($column) . ' ' . $operator . " ? ESCAPE '" . static::LIKE_ESCAPE_CHARACTER . "'",
            ['%' . $this->escapeLikeWildcards((string) $searchTerm, $baseQuery->getConnection()->getDriverName()) . '%']
        );
    }


    /**
     * Escape the LIKE wildcards in the given value, using LIKE_ESCAPE_CHARACTER
     *
     * @param  string $value
     * @param  string $driver
     *
     * @return string
     */
    public function escapeLikeWildcards(string $value, string $driver = '')
    {

        $escape = static::LIKE_ESCAPE_CHARACTER;

        $characters = [$escape, '%', '_'];

        // SQL Server also treats [ as a wildcard (character ranges)
        if ($driver === 'sqlsrv') {

            $characters[] = '[';
        }

        $replacements = [];

        foreach ($characters as $character) {

            $replacements[$character] = $escape . $character;
        }

        return strtr($value, $replacements);
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__.'/../config/eloquent-wherelike.php',
            'eloquent-wherelike'
        );
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__.'/../config/eloquent-wherelike.php' => base_path('config/eloquent-wherelike.php'),
        ], 'config');

        $self = $this;

        Builder::macro('whereLike', function ($attributes, $searchTerm, bool $withTrash = false) use ($self) {

            $this->where(function (Builder $query) use ($attributes, $searchTerm, $withTrash, $self) {

                $searchTerms = array_filter(
                    array_map('trim', explode(' ', (string) $searchTerm)),
                    function ($term) {
                        return $term !== '';
                    }
                );

                foreach ($searchTerms as $searchTerm) {

                    foreach (Arr::wrap($attributes) as $attribute) {

                        $query->when(
                            Str::contains($attribute, '.'),
                            function (Builder $query) use ($attribute, $searchTerm, $withTrash, $self) {

                                $relations = explode('.', $attribute);

                                array_shift($relations);

                                $self->resolveRelationDependency($query, $searchTerm, $relations, $withTrash);
                            },
                            function (Builder $query) use ($attribute, $searchTerm, $self) {

                                $self->addLikeConstraint($query, 'orWhere', $attribute, $searchTerm);
                            }
                        );
                    }
                }
            });

            return $this;
        });
    }

}
