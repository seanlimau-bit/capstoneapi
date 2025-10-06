@extends('layouts.admin')
@section('title','Fields Management')

@section('content')
  @include('admin.components.page-header', [
    'title' => 'Fields Management',
    'subtitle' => 'Manage all fields in the math learning system',
    'breadcrumbs' => [['title'=>'Dashboard','url'=>url('/admin')],['title'=>'Fields']],
    'actions'=>[
      ['text'=>'Create New Field','url'=>route('admin.fields.create'),'icon'=>'plus','class'=>'primary'],
    ]
  ])

  @livewire('admin.fields.field-index')
@endsection
