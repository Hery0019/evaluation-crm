@extends('layouts.master')

@section('content')
<div class="container">
    <h2>Import some sh</h2>
    
    <!-- Messages d'alerte -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    
    <div class="col-sm-10">
        <form action="{{ route('import.csv') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                {!! Form::label('projects_csv', __('Import Projects CSV'). ':', ['class' => 'control-label thin-weight']) !!}
                <input type="file" name="csv_file1" class="form-control" required accept=".csv">
                <small class="form-text text-muted">Please upload the projects CSV file</small>
            </div>
            
            <div class="form-group">
                {!! Form::label('tasks_csv', __('Import Tasks CSV'). ':', ['class' => 'control-label thin-weight']) !!}
                <input type="file" name="csv_file2" class="form-control" required accept=".csv">
                <small class="form-text text-muted">Please upload the tasks CSV file</small>
            </div>

            <div class="form-group">
                {!! Form::label('tasks_csv', __('Import Tasks CSV'). ':', ['class' => 'control-label thin-weight']) !!}
                <input type="file" name="csv_file3" class="form-control" required accept=".csv">
                <small class="form-text text-muted">Please select the rest of this sh</small>
            </div>
            
            <button type="submit" class="btn btn-md btn-success">
                <i class="fas fa-upload mr-2"></i> {{ __('Import All Files') }}
            </button>
        </form>
    </div>
</div>

@section('scripts')
<script>
// Fermer automatiquement les alertes après 5 secondes
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>
@endsection
@endsection