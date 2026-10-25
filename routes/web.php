<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', fn () => response()->json(['ok' => true, 'app' => 'qa-db-laravel-rw']));

// QA fixture: write and read one row through the migrated qa_rows table, on whatever driver .env names.
Route::get('/dbrw', function (Request $request) {
    $db = DB::connection();
    if ($tag = $request->query('tag')) {
        $db->table('qa_rows')->insert(['tag' => $tag, 'created_at' => now(), 'updated_at' => now()]);
    }
    $name = match ($db->getDriverName()) {
        'pgsql' => $db->selectOne('SELECT current_database() AS d')->d,
        'mysql', 'mariadb' => $db->selectOne('SELECT DATABASE() AS d')->d,
        default => $db->getDatabaseName(),
    };

    return response()->json([
        'ok' => true, 'driver' => $db->getDriverName(), 'db' => $name,
        'pdo_pgsql' => extension_loaded('pdo_pgsql'), 'php' => PHP_VERSION,
        'migrated' => Schema::hasTable('qa_rows') && Schema::hasTable('migrations'),
        'rows' => $db->table('qa_rows')->orderBy('id')->get(['id', 'tag']),
    ]);
});
