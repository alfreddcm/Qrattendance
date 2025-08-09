<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManageDailySessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:manage-daily {--clean-old : Clean up old expired sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage daily attendance sessions - expire old ones and prepare for new day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Managing daily attendance sessions...');
        
        try {
            // Expire old sessions
            $expiredCount = $this->expireOldSessions();
            $this->info("Expired {$expiredCount} old sessions.");
            
            // Clean up old sessions if requested
            if ($this->option('clean-old')) {
                $cleanedCount = $this->cleanOldSessions();
                $this->info("Cleaned up {$cleanedCount} old sessions from database.");
            }
            
            // Log session summary
            $this->displaySessionSummary();
            
            $this->info('Daily session management completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error managing daily sessions: ' . $e->getMessage());
            Log::error('Daily session management failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Expire old active sessions
     */
    private function expireOldSessions()
    {
        $now = Carbon::now('Asia/Manila');
        
        $expiredSessions = AttendanceSession::where('status', 'active')
            ->where('expires_at', '<', $now)
            ->get();
            
        foreach ($expiredSessions as $session) {
            $session->update([
                'status' => 'expired',
                'closed_at' => $now
            ]);
            
            Log::info('Session auto-expired', [
                'session_id' => $session->id,
                'session_name' => $session->session_name,
                'teacher_id' => $session->teacher_id,
                'expired_at' => $now->format('Y-m-d H:i:s')
            ]);
        }
        
        return $expiredSessions->count();
    }
    
    /**
     * Clean up old sessions (older than 7 days)
     */
    private function cleanOldSessions()
    {
        $cutoffDate = Carbon::now('Asia/Manila')->subDays(7);
        
        $oldSessions = AttendanceSession::where('status', '!=', 'active')
            ->where('created_at', '<', $cutoffDate)
            ->get();
            
        $count = $oldSessions->count();
        
        foreach ($oldSessions as $session) {
            Log::info('Cleaning old session', [
                'session_id' => $session->id,
                'session_name' => $session->session_name,
                'created_at' => $session->created_at->format('Y-m-d H:i:s')
            ]);
        }
        
        AttendanceSession::where('status', '!=', 'active')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
            
        return $count;
    }
    
    /**
     * Display current session summary
     */
    private function displaySessionSummary()
    {
        $activeSessions = AttendanceSession::where('status', 'active')->count();
        $expiredSessions = AttendanceSession::where('status', 'expired')->count();
        $closedSessions = AttendanceSession::where('status', 'closed')->count();
        
        $this->table(
            ['Status', 'Count'],
            [
                ['Active', $activeSessions],
                ['Expired', $expiredSessions],
                ['Closed', $closedSessions]
            ]
        );
        
        // Show today's active sessions
        $todaySessions = AttendanceSession::where('status', 'active')
            ->whereDate('started_at', Carbon::today('Asia/Manila'))
            ->with('teacher', 'semester')
            ->get();
            
        if ($todaySessions->count() > 0) {
            $this->info("\nToday's Active Sessions:");
            $tableData = [];
            foreach ($todaySessions as $session) {
                $tableData[] = [
                    $session->id,
                    $session->teacher->name ?? 'Unknown',
                    $session->semester->name ?? 'Unknown',
                    $session->expires_at->format('M j, Y g:i A'),
                    $session->access_count
                ];
            }
            
            $this->table(
                ['ID', 'Teacher', 'Semester', 'Expires At', 'Access Count'],
                $tableData
            );
        }
    }
}
