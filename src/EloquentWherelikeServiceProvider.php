<?php

namespace Touhidurabir\EloquentWherelike;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class EloquentWherelikeServiceProvider extends ServiceProvider {
    
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
    public function resolveRelationDependency(   Builder $builder, 
                                                            $searchTerm,
                                                    array   $relations           = [],
                                                    bool    $withTrash           = false,
                                                    string  $relationalCondition = 'orWhereHas') {

        $self = $this;

        if ( empty($relations) ) { 
			
			return $builder; 
		}

        $relation = array_shift($relations);
        
        $relationParams  = Str::contains($relation, '[') && Str::contains($relation, ']')
                               ? explode(',' ,preg_replace('/(.*)\[(.*)\](.*)/sm', '\2', $relation))
                               : [];
        $relationName    = preg_replace('/\[[\s\S]+?\]/', '', $relation);

        return 
            $builder->{$relationalCondition}($relationName, function ($query) use ($relationParams, $searchTerm, $relations, $withTrash, $self) {
                
                $query = $self->resolveRelationDependency($query, $searchTerm, $relations, $withTrash, 'whereHas');

                $method = 'where';

                foreach ($relationParams as $relParam) {

                    if ( $withTrash ) {

                        $query = $query->withTrashed();
                    }

                    $query->{$method}(
                        trim($relParam), 
                        config('eloquent-wherelike.operator') ?? 'LIKE', 
                        "%{$searchTerm}%"
                    );
					
                    $method = 'orWhere';
                }
            });
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/eloquent-wherelike.php', 'eloquent-wherelike'
        );
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {

        $this->publishes([
            __DIR__.'/../config/eloquent-wherelike.php' => base_path('config/eloquent-wherelike.php'),
        ], 'config');

        $self = $this;
        
        Builder::macro('whereLike', function ($attributes, $searchTerm, bool $withTrash = false) use ($self) {

            $this->where(function (Builder $query) use ($attributes, $searchTerm, $withTrash, $self) {

                $searchTerms = array_map('trim', explode(' ', $searchTerm));

                foreach ($searchTerms as $searchTerm) {
                    
                    foreach (Arr::wrap($attributes) as $attribute) {

                        $query->when(
                            Str::contains($attribute, '.'),
    
                            function (Builder $query) use ($attribute, $searchTerm, $withTrash, $self) {
    
                                $relations = explode('.', $attribute);
    
                                array_shift($relations);
    
                                $self->resolveRelationDependency($query, $searchTerm, $relations, $withTrash);
                            },
    
                            function (Builder $query) use ($attribute, $searchTerm) {

                                $query->orWhere(
                                    $attribute, 
                                    config('eloquent-wherelike.operator') ?? 'LIKE', 
                                    "%{$searchTerm}%"
                                );
                            }
                        );
                    }
                }
            });

            return $this;
        });
    }

}