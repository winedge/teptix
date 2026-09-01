@extends('master')

@section('content')
<div class="container">
    <h2>Create Sponsership</h2>
    <form method="POST" action="{{ route('admin.Seat.storeSponser') }}">
        @csrf
        <div class="form-group">
            <label>Sponsership Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Details</label>
            <textarea name="details" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Add Sponser</button>
    </form>
</div>
@endsection
