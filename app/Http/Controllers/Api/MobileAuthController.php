<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'db_connection' => ['required', 'string'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'string'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['required', 'string'],
        ]);

        if (strtolower($data['db_connection']) !== 'pgsql') {
            return response()->json([
                'message' => 'Seule la connexion PostgreSQL (pgsql) est acceptee.',
            ], 422);
        }

        Config::set('database.connections.mobile_pgsql', [
            'driver' => 'pgsql',
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'],
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        DB::purge('mobile_pgsql');

        try {
            DB::connection('mobile_pgsql')->getPdo();
            DB::connection('mobile_pgsql')->select('select 1');
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Connexion PostgreSQL impossible.',
                'error' => $exception->getMessage(),
            ], 401);
        }

        return response()->json([
            'token' => Str::random(60),
            'message' => 'Connexion reussie.',
        ]);
    }
}
