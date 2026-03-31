@extends('layouts.admin')

@section('content')

<div class="container">

    <div class="card mb-3">
        <div class="card-body">

            <h4>{{ $voter->full_name }}</h4>

            <div>National ID: {{ $voter->national_id }}</div>
            <div>Voter No: {{ $voter->voter_no }}</div>
            <div>Location: {{ $voter->location }}</div>

            <div>
                Delegate:
                {{ $voter->assignedDelegate->name ?? 'Not Assigned' }}
            </div>

            <div>
                @if($voter->is_voted)
                    <span class="badge bg-success">Voted</span>
                @else
                    <span class="badge bg-secondary">Not Voted</span>
                @endif
            </div>

        </div>
    </div>

    <div class="mb-3">
        @if(($voter->actionableVoterNotes ?? collect())->where('note_type', 'transportation')->count())
            <span class="badge bg-danger">🚗 Needs Transport</span>
        @endif

        @if(($voter->actionableVoterNotes ?? collect())->where('priority', 'high')->count())
            <span class="badge bg-warning">🔥 High Priority</span>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">Structured Notes</div>

        <div class="card-body">

            <form method="POST" action="{{ route('voters.notes.store', $voter) }}">
                @csrf

                <div class="row">

                    <div class="col-md-3">
                        <select name="note_type" class="form-control" required>
                            <option value="general">General</option>
                            <option value="transportation">🚗 Transport</option>
                            <option value="persuasion">🧠 Persuasion</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="requires_action" class="form-control">
                            <option value="0">No Action</option>
                            <option value="1">Action</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <input type="text" name="content" class="form-control" placeholder="Note..." required>
                    </div>

                </div>

                <button class="btn btn-primary mt-2">Add Note</button>
            </form>

            <hr>

            @forelse(($voter->voterNotes ?? collect()) as $note)
                <div class="border p-2 mb-2">

                    <strong>{{ $note->note_type }}</strong>

                    @if($note->requires_action)
                        <span class="badge bg-danger">Action</span>
                    @endif

                    <div>{{ $note->content }}</div>

                    @if($note->creator)
                        <small class="text-muted">By: {{ $note->creator->name }}</small>
                    @endif

                </div>
            @empty
                <div class="text-muted">No structured notes yet.</div>
            @endforelse

        </div>
    </div>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card">
    <div class="card-header">Relationships</div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('voters.relationships.store', $voter) }}">
            @csrf

            <div class="row">

                <div class="col-md-4 mb-3 position-relative">
                    <label class="form-label">Search existing voter</label>

                    <input type="text"
                           id="voter-search"
                           class="form-control"
                           placeholder="🔎 Search by name or national ID">

                    <input type="hidden" name="related_voter_id" id="selected-voter-id">

                    <div id="search-results" class="search-results"></div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">If unknown, write temporary name</label>

                    <input type="text"
                           name="related_name"
                           class="form-control"
                           placeholder="Example: Wife of Ahmad / Son / Relative">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship Type</label>

                    <select name="relationship_type" class="form-control" required>
                        <option value="spouse">Spouse</option>
                        <option value="son">Son</option>
                        <option value="daughter">Daughter</option>
                        <option value="brother">Brother</option>
                        <option value="sister">Sister</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="relative">Relative</option>
                        <option value="friend">Friend</option>
                        <option value="neighbor">Neighbor</option>
                        <option value="influencer">Influencer</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Primary Influencer</label>

                    <select name="is_primary_influencer" class="form-control" required>
                        <option value="1">Primary</option>
                        <option value="0" selected>Secondary</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Influence Level</label>

                    <select name="influence_level" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Notes</label>

                    <input type="text"
                           name="notes"
                           class="form-control"
                           placeholder="Example: Promised they will come together">
                </div>

                <div class="col-md-12">
                    <button class="btn btn-success">Add Relationship</button>
                </div>

            </div>
        </form>

        <hr>

        @forelse(($voter->relationships ?? collect()) as $rel)
            <div class="border p-2 mb-2">
                <strong>{{ ucfirst($rel->relationship_type) }}</strong>

                @if($rel->relatedVoter)
                    → {{ $rel->relatedVoter->full_name }}
                @elseif($rel->related_name)
                    → {{ $rel->related_name }}
                    <span class="badge bg-warning text-dark">Unconfirmed</span>
                @endif

                <div class="text-muted">
                    Influence: {{ ucfirst($rel->influence_level) }}
                </div>

                @if($rel->is_primary_influencer)
                    <span class="badge bg-success">Primary Influencer</span>
                @endif

                @if($rel->notes)
                    <div>{{ $rel->notes }}</div>
                @endif
            </div>
        @empty
            <div class="text-muted">No relationships yet.</div>
        @endforelse

    </div>
</div>

</div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    let timer;

    const input = document.getElementById('voter-search');
    const resultsBox = document.getElementById('search-results');
    const hiddenInput = document.getElementById('selected-voter-id');

    if (!input || !resultsBox || !hiddenInput) return;

    const SEARCH_URL = "{{ route('voters.search.simple') }}";

    input.addEventListener('input', function () {
        clearTimeout(timer);

        const query = this.value.trim();

        hiddenInput.value = '';

        if (query.length < 2) {
            resultsBox.innerHTML = '';
            return;
        }

        timer = setTimeout(() => {
            fetch(`${SEARCH_URL}?q=${encodeURIComponent(query)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Search request failed');
                    return res.json();
                })
                .then(data => {
                    if (!data.length) {
                        resultsBox.innerHTML = '<div class="search-item">No results</div>';
                        return;
                    }

                    let html = '';

                    data.forEach(voter => {
                        html += `
                            <div class="search-item"
                                 data-id="${voter.id}"
                                 data-name="${voter.full_name}">
                                ${voter.full_name} (${voter.national_id})
                            </div>
                        `;
                    });

                    resultsBox.innerHTML = html;
                })
                .catch(() => {
                    resultsBox.innerHTML = '<div class="search-item">Search error</div>';
                });
        }, 300);
    });

    resultsBox.addEventListener('click', function (e) {
        const item = e.target.closest('.search-item');
        if (!item) return;

        hiddenInput.value = item.dataset.id;
        input.value = item.dataset.name;
        resultsBox.innerHTML = '';
    });
});
</script>

<style>
    .search-results {
        position: absolute;
        background: #fff;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
    }

    .search-item {
        padding: 8px;
        cursor: pointer;
    }

    .search-item:hover {
        background: #f3f4f6;
    }
</style>
