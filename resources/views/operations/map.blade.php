@extends('layouts.admin')

@section('content')

<h1>خريطة المراكز الانتخابية</h1>

<div class="card">

<h2>المراكز الانتخابية</h2>

<p>تعرض الخريطة جميع المراكز الانتخابية مع موقعها الجغرافي.</p>

<div class="map-wrapper">
    <div id="map"></div>
</div>

</div>


<div class="card">

<h2>تفاصيل المراكز</h2>

<table class="admin-table">

<thead>
<tr>
<th>المركز</th>
<th>الموقع</th>
<th>المضمونين</th>
<th>صوتوا</th>
<th>المتبقي</th>
</tr>
</thead>

<tbody>

@foreach($centers as $center)

<tr>

<td>{{ $center->name }}</td>

<td>{{ $center->location }}</td>

<td>{{ $center->supporters }}</td>

<td>{{ $center->supporters_voted }}</td>

<td>{{ $center->supporters_remaining }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>


<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
let map;

/* ===============================
INIT MAP
=============================== */
function initMap() {

    const el = document.getElementById('map');
    if (!el) return;

    map = L.map('map').setView([31.7035, 35.2200], 14);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { maxZoom: 19 }
    ).addTo(map);
}

/* ===============================
RENDER MARKERS
=============================== */
function renderMarkers(centers){

    if (!map) return;

    centers.forEach(center => {

        if (!center.latitude || !center.longitude) return;

        const marker = L.marker([
            center.latitude,
            center.longitude
        ]).addTo(map);

        marker.bindPopup(`
            <strong>${center.name}</strong><br>
            المضمونين: ${center.supporters}<br>
            صوتوا: ${center.supporters_voted}<br>
            المتبقي: ${center.supporters_remaining}
        `);
    });

    // 🔥 هذا يحل المشكلة 90% من الحالات
    setTimeout(() => {
        map.invalidateSize();
    }, 300);
}

/* ===============================
BOOT
=============================== */
document.addEventListener("DOMContentLoaded", function () {

    initMap();

    const centers = @json($centers);

    renderMarkers(centers);

});
</script>

@endsection
