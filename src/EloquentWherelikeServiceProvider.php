<?php

namespace Touhidurabir\EloquentWherelike;

use Illuminate\Support\ServiceProvider;

class EloquentWherelikeServiceProvider extends ServiceProvider {
    
    /**
     * Recursively resolve the chained relational dependency
     *
     * @param  Builder  $builder
     * @param  Array    $relations
     * @param  Mixed    $searchTerm
     * @param  String   $relationalCondition
     *
     * @return Builder
     */
    public function resolveRelationDependency(Builder $builder, 
                                              array $relations = [], 
                                              $searchTerm, 
                                              $relationalCondition = 'orWhereHas') {

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
            $builder->{$relationalCondition}($relationName, function ($query) use ($relationParams, $searchTerm, $relations, $self) {
                
                $query = $self->resolveRelationDependency($query, $relations, $searchTerm, 'whereHas');

                $method = 'where';

                foreach ($relationParams as $relParam) {

                    $query->{$method}(trim($relParam), 'ILIKE', "%{$searchTerm}%");
					
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
        
        //
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {

        $self = $this;
        
        Builder::macro('whereLike', function ($attributes, string $searchTerm) use ($self) {

            $this->where(function (Builder $query) use ($attributes, $searchTerm, $self) {

                $searchTerms = array_map('trim', explode(' ', $searchTerm));

                foreach ($searchTerms as $searchTerm) {
                    
                    foreach (Arr::wrap($attributes) as $attribute) {

                        $query->when(
                            Str::contains($attribute, '.'),
    
                            function (Builder $query) use ($attribute, $searchTerm, $self) {
    
                                $relations = explode('.', $attribute);
    
                                array_shift($relations);
    
                                $self->resolveRelationDependency($query, $relations, $searchTerm);
                            },
    
                            function (Builder $query) use ($attribute, $searchTerm) {

                                $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                            }
                        );
                    }
                }
            });

            return $this;
        });
    }

}