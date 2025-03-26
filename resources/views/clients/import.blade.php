

@extends('layouts.master')

@section('content')
<div class="container">
    <h2>Import some clients</h2>
    
<div class="col-sm-10">
    <form action="{{ route('import-client.csv') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            {!! Form::label('csv_file', __('Import CSV'). ':', ['class' => 'control-label thin-weight']) !!}
            <input type="file" name="csv_file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-md btn-success">
            {{ __('Importer CSV') }}
        </button>
    </form>
</div>
@stop