@extends('layouts.app')

@section('title', $page->title . ' - Tenant Trust')

@if($page->meta_description)
    @section('meta_description', $page->meta_description)
@endif

@section('content')
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>{{ $page->title }}</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="page-content" style="max-width: 800px;">
                {!! $page->content !!}
            </div>
        </div>
    </section>
@endsection
