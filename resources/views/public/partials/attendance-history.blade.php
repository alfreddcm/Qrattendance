@if(isset($recentAttendance) && $recentAttendance->count() > 0)
    @foreach($recentAttendance as $log)
        @php
            $timeType = data_get($log, 'display_type', 'TIME IN');
            $displayTime = data_get($log, 'display_time', '---');
            $timeInfo = $timeType . ': ' . $displayTime;
            $studentPicture = data_get($log, 'student.picture');
            $studentName = data_get($log, 'student.name', '---');
            $sectionName = data_get($log, 'student.section.name', '---');
        @endphp
        <div class="scan-card">
            <div class="scan-card-header">{{ $timeInfo }}</div>
            <div class="scan-card-body">
                @if($studentPicture)
                    <img src="{{ $studentPicture }}" alt="Student">
                @else
                    <div class="scan-photo-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
            </div>
            <div class="scan-card-footer">
                <div class="scan-card-name">{{ $studentName }}</div>
                <div class="scan-card-section">{{ $sectionName }}</div>
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
