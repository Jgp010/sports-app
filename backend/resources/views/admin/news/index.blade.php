@extends('admin.layout')
@section('title', '賽事消息｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">CONTENT</p><h1>賽事消息</h1></div><a class="button primary" href="{{ route('admin.news.create') }}">新增消息</a></div>
<form class="filters" method="get"><label class="filter-field wide">消息標題<input name="q" value="{{ request('q') }}" placeholder="輸入標題"></label><label class="filter-field">狀態<select name="status"><option value="">全部狀態</option>@foreach(['draft'=>'草稿','scheduled'=>'排程','published'=>'已發布','archived'=>'已封存'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></label><button class="button" type="submit">搜尋</button></form>
<section class="panel"><table><thead><tr><th>標題</th><th>運動</th><th>狀態</th><th>發布時間</th><th></th></tr></thead><tbody>
@forelse($posts as $post)<tr><td><strong>{{ $post->title }}</strong><small>{{ $post->summary }}</small></td><td>{{ $post->sport->name }}</td><td><span class="badge {{ $post->status }}">{{ ['draft'=>'草稿','scheduled'=>'排程','published'=>'已發布','archived'=>'已封存'][$post->status] ?? $post->status }}</span></td><td>{{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}</td><td class="actions"><a href="{{ route('admin.news.edit', $post) }}">編輯</a><form method="post" action="{{ route('admin.news.destroy', $post) }}" onsubmit="return confirm('確定要刪除這篇消息？')">@csrf @method('DELETE')<button class="link-button danger">刪除</button></form></td></tr>
@empty<tr><td colspan="5" class="empty">找不到符合條件的消息。</td></tr>@endforelse
</tbody></table></section>
<div class="pager">@if($posts->previousPageUrl())<a href="{{ $posts->previousPageUrl() }}">上一頁</a>@endif<span>第 {{ $posts->currentPage() }} / {{ $posts->lastPage() }} 頁</span>@if($posts->nextPageUrl())<a href="{{ $posts->nextPageUrl() }}">下一頁</a>@endif</div>
@endsection
