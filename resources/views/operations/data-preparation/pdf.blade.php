@php
    $arabic = new \ArPHP\I18N\Arabic();
@endphp

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
        font-family: 'Amiri';
        font-style: normal;
        font-weight: normal;
        src: url('{{ public_path("fonts/Amiri-Regular.ttf") }}') format('truetype');
    }

    @font-face {
        font-family: 'Amiri';
        font-style: normal;
        font-weight: bold;
        src: url('{{ public_path("fonts/Amiri-Bold.ttf") }}') format('truetype');
    }

    body {
        font-family: 'Amiri', sans-serif;
        direction: rtl;
        text-align: right;
        unicode-bidi: plaintext;
        font-size: 13px;
    }

    h2 {
        text-align: center;
        margin-bottom: 10px;
    }

    .filters {
        margin-bottom: 10px;
        font-size: 12px;
        color: #555;
        text-align: right;
    }

    table {
        width: 100%;
        border-collapse: collapse;

        direction: rtl;
    }

    th, td {
        border: 1px solid #ccc;
        padding: 3px 5px;
        text-align: center;
        vertical-align: middle;
        line-height: 1.2;
        font-size: 12px;
    }

    th {
        background: #eee;
        font-weight: bold;
    }

    .totals {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.box {
    flex: 1 1 18%;
    border: 1px solid #ccc;
    padding: 10px;
    text-align: center;
    border-radius: 6px;
    font-weight: bold;
    background: #f9fafb;
}
.box.total { background: #e5e7eb; }
.box.supporter { background: #dcfce7; }
.box.leaning { background: #dbeafe; }
.box.undecided { background: #fef3c7; }
.box.opposed { background: #fee2e2; }

.totals-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    margin-bottom: 10px;
}

.totals-table td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
    font-weight: bold;
    font-size: 12px;
}

/* colors */
.total { background: #e5e7eb; }
.supporter { background: #dcfce7; }
.leaning { background: #dbeafe; }
.undecided { background: #fef3c7; }
.opposed { background: #fee2e2; }
    </style>
</head>
<body>

<h2>{{ $arabic->utf8Glyphs('تقرير الناخبين') }}</h2>

<div class="filters">
    @if($filters['center_id'] ?? false) مركز محدد |
    @endif

    @if($filters['status'] ?? false)
    {{ $arabic->utf8Glyphs('الحالة') }}:
    {{ $arabic->utf8Glyphs(
        match($filters['status']) {
            'supporter' => 'مضمون',
            'leaning' => 'يميل',
            'undecided' => 'متردد',
            'opposed' => 'ضد',
            'traveling' => 'مسافر',
            default => 'غير معروف',
        }
    ) }}
@endif

    @if($filters['priority'] ?? false) الأولوية: {{ $filters['priority'] }} |
    @endif

    @if($filters['family_name'] ?? false) العائلة: {{ $filters['family_name'] }} |
    @endif
</div>

<table class="totals-table">
    <tr>
        <td class="total">
            {{ $arabic->utf8Glyphs('إجمالي') }}: {{ $totals->total }}
        </td>
        <td class="supporter">
            {{ $arabic->utf8Glyphs('مضمون') }}: {{ $totals->supporter }}
        </td>
        <td class="leaning">
            {{ $arabic->utf8Glyphs('يميل') }}: {{ $totals->leaning }}
        </td>
        <td class="undecided">
            {{ $arabic->utf8Glyphs('متردد') }}: {{ $totals->undecided }}
        </td>
        <td class="opposed">
            {{ $arabic->utf8Glyphs('ضد') }}: {{ $totals->opposed }}
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>{{ $arabic->utf8Glyphs('الاسم') }}</th>
            <th>{{ $arabic->utf8Glyphs('المركز') }}</th>
            <th>{{ $arabic->utf8Glyphs('الحالة') }}</th>
            <th>{{ $arabic->utf8Glyphs('المندوب') }}</th>
            <th>{{ $arabic->utf8Glyphs('الملاحظات') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($voters as $v)
        <tr>
            <td>{{ $v->full_name }}</td>
            <td>{{ $v->polling_center_name }}</td>
            <td>{{ $v->support_status }}</td>
            <td>{{ $v->delegate_name }}</td>
            <td>
                @if(!empty($v->notes))
                    @foreach($v->notes as $note)
                        <div style="font-size:11px; margin-bottom:4px;">
                            - {{ $note }}
                        </div>
                    @endforeach
                @else
                    -
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
