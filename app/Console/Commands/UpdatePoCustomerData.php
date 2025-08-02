<?php
// app/Console/Commands/UpdatePoCustomerData.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PoApprovedService;
use Illuminate\Support\Facades\DB;

class UpdatePoCustomerData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'po:update-customer-data 
                            {--limit=100 : Number of records to process}
                            {--all : Process all missing records}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update missing customer_name and item_count data for existing PO approvals';

    protected $poApprovedService;

    /**
     * Create a new command instance.
     */
    public function __construct(PoApprovedService $poApprovedService)
    {
        parent::__construct();
        $this->poApprovedService = $poApprovedService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting PO Customer Data Update Process...');
        $this->newLine();

        // ตรวจสอบการเชื่อมต่อฐานข้อมูล
        if (!$this->checkDatabaseConnections()) {
            return Command::FAILURE;
        }

        // นับจำนวนรายการที่ต้อง update
        $missingCount = $this->getMissingDataCount();
        
        if ($missingCount === 0) {
            $this->info('✅ All PO approval records already have customer data!');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$missingCount} records missing customer data");
        $this->newLine();

        // กำหนด limit
        $limit = $this->option('all') ? $missingCount : (int)$this->option('limit');
        
        if ($limit > $missingCount) {
            $limit = $missingCount;
        }

        $this->info("🎯 Processing {$limit} records...");

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
            $this->showDryRunPreview($limit);
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->option('all') && $missingCount > 100) {
            if (!$this->confirm("This will process {$limit} out of {$missingCount} records. Continue?")) {
                $this->info('❌ Operation cancelled');
                return Command::SUCCESS;
            }
        }

        // แสดง progress bar
        $progressBar = $this->output->createProgressBar($limit);
        $progressBar->setFormat('verbose');

        $successCount = 0;
        $errorCount = 0;
        $processedBatch = 0;
        $batchSize = 50;

        // ประมวลผลเป็น batch
        while ($processedBatch < $limit) {
            $currentBatchSize = min($batchSize, $limit - $processedBatch);
            
            $result = $this->poApprovedService->updateMissingCustomerData($currentBatchSize);
            
            if ($result['success']) {
                $successCount += $result['updated'];
                $errorCount += $result['errors'];
                $processedBatch += $result['processed'];
                
                $progressBar->advance($result['processed']);
            } else {
                $this->error("\n❌ Batch processing failed: " . $result['error']);
                break;
            }

            // หยุดพัก 100ms ระหว่าง batch เพื่อไม่ให้ database ทำงานหนักเกินไป
            usleep(100000);
        }

        $progressBar->finish();
        $this->newLine(2);

        // แสดงผลลัพธ์
        $this->displayResults($successCount, $errorCount, $processedBatch);

        return Command::SUCCESS;
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     */
    private function checkDatabaseConnections()
    {
        $this->info('🔍 Checking database connections...');

        try {
            // ทดสอบ Modern Database
            DB::connection('modern')->getPdo();
            $this->info('✅ Modern Database (Romar128): Connected');

            // ทดสอบ Legacy Database
            DB::connection('legacy')->getPdo();
            $this->info('✅ Legacy Database (Romar1): Connected');

            return true;

        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * นับจำนวนรายการที่ขาดข้อมูล
     */
    private function getMissingDataCount()
    {
        try {
            $count = DB::connection('modern')
                ->table('po_approvals')
                ->where(function($query) {
                    $query->whereNull('customer_name')
                          ->orWhereNull('item_count');
                })
                ->count();

            return $count;
        } catch (\Exception $e) {
            $this->error('Error counting missing records: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * แสดงตัวอย่างข้อมูลที่จะ update (dry run)
     */
    private function showDryRunPreview($limit)
    {
        try {
            $sampleRecords = DB::connection('modern')
                ->table('po_approvals')
                ->whereNull('customer_name')
                ->orWhereNull('item_count')
                ->select('id', 'po_docno', 'customer_name', 'item_count', 'created_at')
                ->limit(min(10, $limit))
                ->get();

            $this->table(
                ['ID', 'PO Number', 'Current Customer', 'Current Items', 'Created'],
                $sampleRecords->map(function($record) {
                    return [
                        $record->id,
                        $record->po_docno,
                        $record->customer_name ?? '❌ Missing',
                        $record->item_count ?? '❌ Missing', 
                        $record->created_at
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info('📝 These records would be updated with customer data from Legacy DB');
        } catch (\Exception $e) {
            $this->error('Error fetching preview data: ' . $e->getMessage());
        }
    }

    /**
     * แสดงผลลัพธ์การประมวลผล
     */
    private function displayResults($successCount, $errorCount, $processedCount)
    {
        $this->info('📈 Update Process Completed!');
        $this->newLine();

        // สถิติผลลัพธ์
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Processed', $processedCount],
                ['Successfully Updated', $successCount],
                ['Errors Encountered', $errorCount],
                ['Success Rate', $processedCount > 0 ? round(($successCount / $processedCount) * 100, 2) . '%' : '0%']
            ]
        );

        // แสดงสถานะปัจจุบัน
        $remainingCount = $this->getMissingDataCount();
        
        if ($remainingCount > 0) {
            $this->warn("⚠️  {$remainingCount} records still need updating");
            $this->info("💡 Run the command again with --limit={$remainingCount} or --all to process remaining records");
        } else {
            $this->info('🎉 All records have been updated successfully!');
        }

        // แนะนำคำสั่งต่อไป
        $this->newLine();
        $this->info('📊 Check the updated data in PO Approved page: /po-approved');
        
        if ($errorCount > 0) {
            $this->warn('📋 Check the logs for detailed error information');
        }
    }

    /**
     * แสดงตัวอย่างการใช้งาน command
     */
    public function showUsageExamples()
    {
        $this->info('📚 Usage Examples:');
        $this->line('');
        $this->line('  Update 100 records (default):');
        $this->line('  <comment>php artisan po:update-customer-data</comment>');
        $this->line('');
        $this->line('  Update 500 records:');
        $this->line('  <comment>php artisan po:update-customer-data --limit=500</comment>');
        $this->line('');
        $this->line('  Update all missing records:');
        $this->line('  <comment>php artisan po:update-customer-data --all</comment>');
        $this->line('');
        $this->line('  Preview what would be updated (dry run):');
        $this->line('  <comment>php artisan po:update-customer-data --dry-run</comment>');
    }
}