<div class="card">

    <table class="admin-table">

        <thead>
            <tr>
                <th>الاسم</th>
                <th>الحالة</th>
                <th>الأولوية</th>
                <th>إدارة</th>
            </tr>
        </thead>

        <tbody>

            @foreach($targets as $voter)

                <tr>

                    <td>{{ $voter->full_name }}</td>

                    <td>
                        @if($voter->support_status == 'undecided')
                            🟡 متردد
                        @elseif($voter->support_status == 'leaning')
                            🟠 يميل
                        @elseif($voter->support_status == 'supporter')
                            🟢 مضمون
                        @endif
                    </td>

                    <td>
                        @if($voter->priority_level == 'high')
                            🔴 عالي
                        @elseif($voter->priority_level == 'medium')
                            🟡 متوسط
                        @else
                            🟢 منخفض
                        @endif
                    </td>

                    <td>
                        <button class="btn">👤 تعيين</button>
                        <button class="btn">📋 مهمة</button>
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>
