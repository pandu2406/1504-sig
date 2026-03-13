<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UnitController;

Route::any('/', [UnitController::class, 'index'])->name('units.index');
Route::post('/units/store', [UnitController::class, 'store'])->name('units.store');
Route::post('/units/bulk-import', [UnitController::class, 'bulkImport'])->name('units.bulk_import');
Route::post('/units/bulk-preview', [UnitController::class, 'bulkPreview'])->name('units.bulk_preview');
Route::get('/units/bulk-history', [UnitController::class, 'bulkHistory'])->name('units.bulk_history');
Route::post('/units/bulk-cancel', [UnitController::class, 'bulkCancel'])->name('units.bulk_cancel');
Route::get('/api/check-duplicate', [UnitController::class, 'checkDuplicate'])->name('api.check_duplicate');
Route::post('/units/{unit}/update', [UnitController::class, 'update'])->name('units.update');
Route::delete('/units/{unit}/delete', [UnitController::class, 'destroy'])->name('units.destroy');
Route::post('/units/{unit}/cancel', [UnitController::class, 'cancel'])->name('units.cancel');
Route::get('/units/export', [UnitController::class, 'export'])->name('units.export');
Route::get('/units/analysis', [App\Http\Controllers\UnitController::class, 'analysis'])->name('units.analysis');
Route::get('/units/rekap', [UnitController::class, 'rekap'])->name('units.rekap');
Route::get('/units/rekap/summary', [UnitController::class, 'getRekapSummary'])->name('units.rekap.summary');
Route::get('/units/tambah-wilayah', [UnitController::class, 'tambahWilayah'])->name('units.tambah_wilayah');
Route::get('/units/rekap/desa/{kdkec}', [UnitController::class, 'getDesaByKecamatan'])->name('units.rekap.desa');
Route::get('/units/rekap/sls/{kdkec}/{kddesa}', [UnitController::class, 'getSlsByDesa'])->name('units.rekap.sls');
Route::get('/units/rekap/tambahan/desa/{kdkec}', [UnitController::class, 'getDesaTambahanByKecamatan'])->name('units.rekap.tambahan.desa');
Route::get('/units/rekap/tambahan/sls/{kdkec}/{kddesa}', [UnitController::class, 'getSlsTambahanByDesa'])->name('units.rekap.tambahan.sls');
Route::get('/units/generate-kode', [UnitController::class, 'showGenerateKodePage'])->name('units.generate_kode');
Route::post('/api/detect-addresses', [UnitController::class, 'detectAddresses'])->name('api.detect_addresses');
Route::get('/units/gap-analysis', [UnitController::class, 'gapAnalysis'])->name('units.gap_analysis');
Route::get('/api/map-stats', [UnitController::class, 'getMapStats'])->name('api.map_stats');
Route::get('/api/villages/{kecCode}', [UnitController::class, 'getVillages'])->name('api.villages');
Route::get('/units/contributions/{username}', [UnitController::class, 'getUserContributions'])->name('units.contributions');
Route::get('/units/daily/{date}', [UnitController::class, 'getDailyLogs'])->name('units.daily');
Route::get('/units/daily-contributors/{date}', [UnitController::class, 'getDailyContributors'])->name('units.daily_contributors');
Route::get('/map-dashboard', [UnitController::class, 'mapDashboard'])->name('units.map_dashboard');
Route::get('/api/map-data', [UnitController::class, 'getMapData'])->name('api.map_data');
Route::get('/api/check-sls', [UnitController::class, 'checkSls'])->name('api.check_sls');
Route::get('/api/desa-geometry/{kec}/{desa}', [UnitController::class, 'getDesaGeometry'])->name('api.desa_geometry'); // New Endpoint
Route::get('/sipw-visualization', [UnitController::class, 'sipwVisualization'])->name('units.sipw_viz');

Route::get('/download-hasil-verifikasi', [UnitController::class, 'downloadVerified'])->name('units.download_verified');

// Bulk Update Routes
Route::get('/unit/bulk-update/login', [\App\Http\Controllers\BulkUpdateController::class, 'loginPage'])->name('units.bulk-update.login_page');
Route::post('/unit/bulk-update/login', [\App\Http\Controllers\BulkUpdateController::class, 'login'])->name('units.bulk-update.login');
Route::get('/unit/bulk-update', [\App\Http\Controllers\BulkUpdateController::class, 'index'])->name('units.bulk-update');
Route::post('/unit/bulk-update/preview', [\App\Http\Controllers\BulkUpdateController::class, 'preview'])->name('units.bulk-update.preview');
Route::post('/unit/bulk-update/execute', [\App\Http\Controllers\BulkUpdateController::class, 'execute'])->name('units.bulk-update.execute');
Route::post('/unit/bulk-update/rollback', [\App\Http\Controllers\BulkUpdateController::class, 'rollback'])->name('units.bulk-update.rollback');
Route::get('/api/sls-list/{kec}/{desa}', [\App\Http\Controllers\BulkUpdateController::class, 'getSlsList']);
