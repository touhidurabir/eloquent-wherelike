# Changelog
All notable changes to this project will be documented in this file.

## [Unreleased]
- Laravel 11, 12 and 13 support added.
- PHPUnit 10/11/12 support for tests.
- Code style formatted to PSR-12 with Laravel Pint (`composer format` / `composer lint`).
- Replaced Travis CI with GitHub Actions (PHP 8.2–8.4 × Laravel 11/12/13, plus a code style check).
- **Behaviour change:** fixed repeated, leading or trailing spaces in the search term producing empty terms. Each
  empty term became `LIKE '%%'`, which matched every row, so a search such as `'john  smith'` returned the whole
  table. Empty terms are now ignored. A search term that is empty or only spaces now adds no constraint at all
  (before, it added `LIKE '%%'`, which matched every row with a non-null value in a searched column).
- `withTrashed()` is now applied once per relation instead of once per relation column.


## [1.0.0] - 2021-10-24
- Initial Release

## [2.0.0] - 2022-01-26
- Dependency Updated

## [2.0.1] - 2022-01-26
- Unnecessary Dependency Removed
- Min PHP version set to 7.4
