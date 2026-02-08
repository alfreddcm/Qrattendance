@php
    use Carbon\Carbon;
@endphp

@if(isset($recentAttendance) && $recentAttendance->count() > 0)
    @foreach($recentAttendance as $log)
        @php
            $timeType = 'TIME IN';
            $lastTime = null;

            if ($log->time_out_pm) {
                $lastTime = Carbon::parse($log->time_out_pm)->format('g:i A');
                $timeType = 'TIME OUT';
            } elseif ($log->time_in_pm) {
                $lastTime = Carbon::parse($log->time_in_pm)->format('g:i A');
                $timeType = 'TIME IN';
            } elseif ($log->time_out_am) {
                $lastTime = Carbon::parse($log->time_out_am)->format('g:i A');
                $timeType = 'TIME OUT';
            } elseif ($log->time_in_am) {
                $lastTime = Carbon::parse($log->time_in_am)->format('g:i A');
                $timeType = 'TIME IN';
            }

            $timeInfo = $lastTime ? "{$timeType}: {$lastTime}" : 'TIME IN: ---';
        @endphp
        <div class="scan-card">
            <div class="scan-card-header">{{ $timeInfo }}</div>
            <div class="scan-card-body">
                @if($log->student && $log->student->picture)
                    <img src="{{ asset('storage/student_pictures/' . $log->student->picture) }}" alt="Student">
                @else
                    <div class="scan-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
            </div>
            <div class="scan-card-footer">
                <div class="scan-card-name">{{ $log->student->name ?? '---' }}</div>
                <div class="scan-card-section">{{ $log->student->section->name ?? '---' }}</div>
            </div>
        </div>
    @endforeach

    @for($i = $recentAttendance->count(); $i < 7; $i++)
        <div class="scan-card">
            <div class="scan-card-header">TIME IN: ---</div>
            <div class="scan-card-body">
                <div class="scan-photo-placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            <div class="scan-card-footer">
                <div class="scan-card-name">---</div>
                <div class="scan-card-section">---</div>
            </div>
        </div>
    @endfor
@else
    @for($i = 0; $i < 7; $i++)
        <div class="scan-card">
            <div class="scan-card-header">TIME IN: ---</div>
            <div class="scan-card-body">
                <div class="scan-photo-placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            <div class="scan-card-footer">
                <div class="scan-card-name">---</div>
                <div class="scan-card-section">---</div>
            </div>
        </div>
    @endfor
@endif
