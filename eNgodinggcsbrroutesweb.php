
// Bulk Update Routes
Route::get('/unit/bulk-update', [\App\Http\Controllers\BulkUpdateController::class, 'index'])->name('units.bulk-update');
Route::post('/unit/bulk-update/execute', [\App\Http\Controllers\BulkUpdateController::class, 'execute']);
Route::post('/unit/bulk-update/rollback', [\App\Http\Controllers\BulkUpdateController::class, 'rollback']);
