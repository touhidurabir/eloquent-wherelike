# Laravel Eloquent WhereLike

An extended laravel eloquent WHERE method to work with sql LIKE operator.

## Inspiration

The idea of this package comes from one of the [Freek's Blog Post](https://freek.dev/1182-searching-models-using-a-where-like-query-in-laravel). I had developed this macro, which is a slightly different variation of what is described there and using it for years. So I have decided to release this as a package, so that I don't have to copy paste same code again and again and at the same time others can use this to save a little bit of their time. 

## Installation

Require the package using composer:

```bash
composer require touhidurabir/eloquent-wherelike
```

To publish the config file:
```bash
php artisan vendor:publish --provider="Touhidurabir\EloquentWherelike\EloquentWherelikeServiceProvider" --tag=config
```

## Configurations

The config file contains one important configuration option; the **operator** which defines the SQL operator that will be used to perform the query. By default it's set to **LIKE**, but you can update it as you see fit. For example, for PostgreSQL, it should be set to **ILIKE**.

## Usage

As this **whereLike** method is defined as a **macro**, just use it like any eloquent method. 

```php
$users = User::whereLike(['email', 'name'], 'search_term')->get();
```

The first param will be the targeted columns to search for and second one is the actual search term. 

One big advantage of this package is that is allows searches based on model relations. For example, say a user as one profile, and the profile table has **first_name** and **last_name** columns. In order to search users whose first name match, we can do following: 

```php
$users = User::whereLike(
    [
        'email', 
        'name',
        '.profile[first, last_name]'
    ], 'search_term'
)->get();
```

Here, notice the syntax 

```php
'.profile[first, last_name]'
```

the initial **dot(.)** defines what is the relation, where the **opeing and closing third bracked([])** define the columns of that relation model/table. In this example, we are also looking into the profile relations `first_name` and `last_name` columns.

A more advance example from one of my projects:

```php
$campaigns = $campaigns->whereLike(
    [
        'title', 
        'description',
        '.user.profile[first_name, last_name]',
        '.categories[name]',
        '.campaigntype[title]',
        '.team[name]'
    ], 
    $search
)->get();
```

In the above example, we are searching for all campaigns based on, not only from campaigns table column, but from it's relation models' columns also. 

Now, if we want to write the whole thing without using this package, we would have to write:

```php
$campaigns = $campaigns->where(function ($query) use ($search) {
    $query
        ->where('title', 'LIKE', '%' . $search . '%')
        ->orWhere('description', 'LIKE', '%' . $search . '%')
        ->orWhereHas('user', function($query) use ($search) {
            $query->whereHas('profile', function($query) use ($search) {
                $query->where('first_name', 'LIKE', '%' . $search . '%')
                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
            });
        })
        ->orWhereHas('categories', function($query) use ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        })
        ->orWhereHas('campaigntype', function($query) use ($search) {
            $query->where('title', 'LIKE', '%' . $search . '%');
        })
        ->orWhereHas('team', function($query) use ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        });
})->get();
```

This **whereLike** saves us so much time this way! 🎉

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](./LICENSE.md)
