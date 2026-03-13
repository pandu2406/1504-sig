$unit = App\Models\Unit::first();
if ($unit) {
echo "Testing with Unit ID: " . $unit->id . "\n";
// Ensure unit has data to cancel
$unit->latitude = -6.20000000;
$unit->longitude = 106.80000000;
$unit->status_keberadaan = 1;
$unit->current_status = 'VERIFIED';
$unit->raw_data = ['sls_idsls' => '1101010001', 'idsls' => '1101010001'];
$unit->save();
echo "Unit Data Set.\n";

$controller = new App\Http\Controllers\UnitController();
$req = new Illuminate\Http\Request();
$req->merge(['username' => 'TestBot']);

try {
echo "Calling cancel...\n";
$res = $controller->cancel($req, $unit);
echo "RESPONSE STATUS: " . $res->getStatusCode() . "\n";
echo "RESPONSE CONTENT: " . $res->getContent() . "\n";
} catch (Exception $e) {
echo "EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
echo $e->getTraceAsString();
}
} else {
echo "NO UNIT FOUND TO TEST.\n";
}
exit();