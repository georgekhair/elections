@extends('layouts.admin')

@section('content')

<h1>🚀 وضع الاقتراع السريع - المشرف</h1>

<input
    type="text"
    id="search"
    placeholder="اكتب الاسم أو الهوية..."
    class="search-input"
    autofocus
>

<div id="results"></div>

@endsection

@section('scripts')

<script>

let timer = null;

document.getElementById('search').addEventListener('input', function() {

    clearTimeout(timer);

    let query = this.value;

    if(query.length < 1){
        document.getElementById('results').innerHTML = '';
        return;
    }

    timer = setTimeout(() => {

        fetch(`{{ route('supervisor.voters') }}?search=${query}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {

            let html = '';

            if(data.length === 0){
                html = '<div class="card">لا يوجد نتائج</div>';
            }

            data.forEach(voter => {

                html += `
                    <div class="voter-card">
                        <div class="name">${voter.full_name}</div>
                        <div class="meta">${voter.national_id}</div>
                        <div class="meta">📍 ${voter.polling_center?.name ?? ''}</div>

                        <div class="meta">
                            👤 ${
                                voter.assigned_delegate
                                ? voter.assigned_delegate.name
                                : 'بدون مندوب'
                            }
                        </div>

                        ${
                            voter.is_voted
                            ? `<div class="voted">✔ تم الاقتراع</div>`
                            : `<button onclick="vote(${voter.id})" class="vote-btn">اقتراع</button>`
                        }
                    </div>
                `;

            });

            document.getElementById('results').innerHTML = html;

        });

    }, 250);

});


function vote(id){

    fetch(`/supervisor/voters/${id}/mark`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(() => {
        document.getElementById('search').dispatchEvent(new Event('input'));
    });

}

</script>
<style>
    .search-input {
    width: 100%;
    padding: 16px;
    font-size: 20px;
    border-radius: 10px;
    border: 1px solid #ddd;
    margin-bottom: 20px;
}

.voter-card {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.name {
    font-size: 18px;
    font-weight: bold;
}

.meta {
    font-size: 13px;
    color: #666;
}

.vote-btn {
    width: 100%;
    margin-top: 10px;
    padding: 14px;
    font-size: 18px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 8px;
}

.voted {
    margin-top: 10px;
    color: green;
    font-weight: bold;
}
</style>
@endsection
