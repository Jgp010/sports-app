@extends('admin.layout')
@section('title', '運動類別｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">TAXONOMY</p><h1>運動類別</h1></div></div>
<div class="editor-grid">
<section class="panel"><h2>現有類別</h2>@foreach($sports as $sport)<form class="sport-row" method="post" action="{{ route('admin.sports.update', $sport) }}">@csrf @method('PUT')<input name="name" value="{{ $sport->name }}" required><input type="number" name="sort_order" value="{{ $sport->sort_order }}" min="0"><label class="check"><input type="checkbox" name="is_active" value="1" @checked($sport->is_active)>啟用</label><button class="button">儲存</button></form>@endforeach</section>
<aside class="panel stack"><h2>新增類別</h2><form method="post" action="{{ route('admin.sports.store') }}" class="stack">@csrf<label>名稱<input name="name" required></label><label>排序<input type="number" name="sort_order" value="0" min="0"></label><button class="button primary">新增</button></form></aside>
</div>
@endsection
