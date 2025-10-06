@extends('layouts.admin')
@section('title','Fields Management')

@section('content')
  @livewire(\App\Livewire\Admin\Fields\FieldIndex::class)
@endsection
