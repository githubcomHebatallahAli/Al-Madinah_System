<?php

namespace App\Console\Commands;

use App\Models\HotelInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredRoomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rooms:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release hotel rooms that passed their checkout date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
               HotelInvoice::releaseExpiredRooms();

        Log::info('✅ تم إرجاع الغرف المنتهية تلقائيًا في: ' . now());

        $this->info('Rooms released successfully.');
    }
    }

