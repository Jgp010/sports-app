@extends('admin.layout')
@php($editing = $post->exists)
@section('title', ($editing ? '編輯' : '新增').'消息｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $editing ? 'EDIT' : 'CREATE' }}</p><h1>{{ $editing ? '編輯消息' : '新增消息' }}</h1></div><a class="button" href="{{ route('admin.news.index') }}">返回列表</a></div>
<form class="editor-grid" method="post" enctype="multipart/form-data" action="{{ $editing ? route('admin.news.update', $post) : route('admin.news.store') }}">@csrf @if($editing)@method('PUT')@endif
<section class="panel stack">
    <label>標題<input name="title" maxlength="120" value="{{ old('title', $post->title) }}" required></label>
    <label>摘要<textarea name="summary" rows="3" maxlength="300" required>{{ old('summary', $post->summary) }}</textarea></label>
    <label>內文（純文字）<textarea name="content" rows="18" maxlength="50000" required>{{ old('content', $post->content) }}</textarea><small>目前採安全的純文字格式；Android 會保留換行顯示。</small></label>
</section>
<aside class="panel stack">
    <label>運動類別<select name="sport_id" required><option value="">請選擇</option>@foreach($sports as $sport)<option value="{{ $sport->id }}" @selected((string)old('sport_id', $post->sport_id)===(string)$sport->id)>{{ $sport->name }}</option>@endforeach</select></label>
    <label>狀態<select name="status" required>@foreach(['draft'=>'草稿','scheduled'=>'排程發布','published'=>'已發布','archived'=>'已封存'] as $key=>$label)<option value="{{ $key }}" @selected(old('status', $post->status ?: 'draft')===$key)>{{ $label }}</option>@endforeach</select></label>
    <label>發布時間<input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"></label>
    <label>賽事時間<input type="datetime-local" name="event_start_at" value="{{ old('event_start_at', $post->event_start_at?->format('Y-m-d\TH:i')) }}"></label>
    <label>地點<input name="venue" maxlength="160" value="{{ old('venue', $post->venue) }}"></label>
    <label>封面圖片<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"><small>JPG、PNG 或 WebP，最大 5 MB。</small></label>
    @if($post->cover_path)<img class="cover-preview" src="{{ route('media.news-cover', ['filename' => basename($post->cover_path)]) }}" alt="目前封面">@endif
    <button class="button primary" type="submit">{{ $editing ? '儲存修改' : '建立消息' }}</button>
</aside>
</form>
@endsection
