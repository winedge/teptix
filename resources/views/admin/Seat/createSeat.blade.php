@extends('master')

@section('content')
<div class="container">
    <h2>Create Seat Table</h2>
    <form method="POST" action="{{ route('admin.Seat.storeSeat') }}">
        @csrf
        <div class="form-group">
            <label>Name of Table</label>
            <input type="text" name="name_of_table" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Number of Seats</label>
            <input type="number" name="number_seat" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Sponsership</label>
            <select name="sponsership_id" class="form-control">
                <option value="">-- None --</option>
                @foreach($sponsers as $sponser)
                    <option value="{{ $sponser->id }}">{{ $sponser->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Prefix Name</label>
            <input type="text" name="prefixname" class="form-control" placeholder="Example: Demo_">
        </div>
        <button type="submit" class="btn btn-primary">Create Seat</button>
    </form>
</div>
@endsection
