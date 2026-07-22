<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Components\Document\Document;

class DocumentCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:document-cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup documents on disk.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("DocumentCleanup::handle - Starting document cleanup");

        $documents = Document::where('expires_at', '<=', Carbon::now())
            ->orderBy('expires_at', 'asc')
            ->get();

        if (count($documents) !== 0) {
            foreach ($documents as $document) {
                try {
                    if (file_exists($document->path)) {
                        unlink($document->path);
                    }
                    $document->delete();
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->info("DocumentCleanup::handle - Found " . count($documents) . " documents for cleanup.");
        } else {
            $this->info("DocumentCleanup::handle - No documents to cleanup.");
        }
    }
}
