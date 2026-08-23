<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GeographicController;
use App\Http\Controllers\Api\DashboardStatsController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\SiteMouvementPopulationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [MobileAuthController::class, 'login']);

// Routes API publiques pour les statistiques du dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [DashboardStatsController::class, 'getStats']);
    Route::get('/province-distribution', [DashboardStatsController::class, 'getProvinceDistribution']);
    Route::get('/trends', [DashboardStatsController::class, 'getTrends']);
    Route::get('/map-sites', [DashboardStatsController::class, 'getMapSites']);
});

// Routes API publiques pour les données géographiques (utilisées par le dashboard public)
Route::prefix('geographic')->group(function () {
    Route::get('/provinces', [GeographicController::class, 'getProvinces']);
    Route::get('/territoires', [GeographicController::class, 'getTerritoires']);
    Route::get('/communes', [GeographicController::class, 'getCommunes']);
    Route::get('/sites', [GeographicController::class, 'getSites']);
    Route::get('/coordinateurs', [GeographicController::class, 'getCoordinateurs']);
    Route::get('/gestionnaires', [GeographicController::class, 'getGestionnaires']);
    Route::get('/categories-sites', [GeographicController::class, 'getCategoriesSites']);
    Route::get('/sites-coordinates', [GeographicController::class, 'getSitesWithCoordinates']);
    Route::get('/provinces/{id}', [GeographicController::class, 'getProvinceDetails']);
    Route::get('/territoires/{id}', [GeographicController::class, 'getTerritoireDetails']);
});

// Routes API pour les mouvements de population (nécessite authentification)
Route::middleware('auth:sanctum')->prefix('mouvements-population')->group(function () {
    Route::get('/', [SiteMouvementPopulationController::class, 'index']);
    Route::post('/', [SiteMouvementPopulationController::class, 'store']);
    Route::get('/{id}', [SiteMouvementPopulationController::class, 'show']);
    Route::get('/site/{siteId}/statistics', [SiteMouvementPopulationController::class, 'statistics']);
});

// Routes API pour les catégories et raisons de mouvements
Route::prefix('mouvements')->group(function () {
    Route::get('/categories', [SiteMouvementPopulationController::class, 'getCategories']);
    Route::get('/raisons', [SiteMouvementPopulationController::class, 'getRaisons']);
});
