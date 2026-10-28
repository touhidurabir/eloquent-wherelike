<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WHERE LIKE SQL OPERATOR
    |--------------------------------------------------------------------------
    |
    | The where like method sql operator . By default set to 'LIKE' but can be
    | updated to according to database driver . For example : for database
    | driver postgresql, it should be set to 'ILIKE' .
    |
    */
    'operator' => 'LIKE',

    /*
    |--------------------------------------------------------------------------
    | ESCAPE LIKE WILDCARDS
    |--------------------------------------------------------------------------
    |
    | By default the "%" and "_" characters in the search term act as SQL LIKE
    | wildcards, so a search for "100%" also matches "1000". Set this to true
    | to escape them so that they only match literally. The query then uses
    | an explicit ESCAPE '!' clause, which works on MySQL, MariaDB, PostgreSQL,
    | SQLite and SQL Server.
    |
    */
    'escape_wildcards' => false,
];
