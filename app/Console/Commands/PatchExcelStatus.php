<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Unit;

class PatchExcelStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:patch-excel-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status_keberadaan from Excel (Column M / Index 12)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = 'e:\Ngoding\gc-sbr1504\Gabungan.xlsx';
        $tempCsv = 'e:\Ngoding\groundcheck_app\temp_patch_status.csv';

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $this->info("Converting Excel to CSV for fast processing...");

        // Python script: Read Excel, keeping Header=1 (row 2), and output id (col 0) and status (col 12)
        // Col 0 is 'idsbr', Col 12 is 'keberadaan_usaha' (based on 0-indexing the data columns)
        // Wait, Header=1 means row 2 is header. Data starts row 3?
        // In ImportExcelData: pd.read_excel(..., header=1).
        // Let's assume the structure is consistent.
        $pythonScript = "import pandas as pd; df = pd.read_excel(r'$file', header=1); df.to_csv(r'$tempCsv', columns=[df.columns[0], df.columns[12]], index=False)";

        // We need to be careful with column referencing by index if names change, but iloc is safer if order is fixed.
        // Let's use iloc to be sure:
        $pythonScript = "import pandas as pd; df = pd.read_excel(r'$file', header=1); output = df.iloc[:, [0, 12]]; output.to_csv(r'$tempCsv', index=False, header=False)";

        $command = "python -c \"$pythonScript\"";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error("Python conversion failed.");
            return 1;
        }

        $this->info("Updating database...");

        $handle = fopen($tempCsv, 'r');
        $count = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $idsbr = $row[0];
            $status = $row[1];

            // Only update if status is valid (not empty)
            if ($idsbr && $status !== '') {
                // We assume idsbr is unique
                $affected = Unit::where('idsbr', $idsbr)->update(['status_keberadaan' => $status]);
                if ($affected) {
                    $updated++;
                }
            }
            $count++;
            if ($count % 1000 == 0)
                $this->info("Processed $count rows...");
        }

        fclose($handle);
        unlink($tempCsv);

        $this->info("Done! Processed $count rows. Updated $updated records.");
        return 0;
    }
}
