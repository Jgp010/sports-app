@extends('admin.layout')
@section('title', '儀表板｜體育消息管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">OVERVIEW</p><h1>儀表板</h1></div><a class="button primary" href="{{ route('admin.news.create') }}">新增消息</a></div>
<section class="stats">
    @foreach(['draft'=>'草稿','scheduled'=>'排程','published'=>'已發布','archived'=>'已封存'] as $key=>$label)
        <article class="stat"><span>{{ $label }}</span><strong>{{ $counts[$key] ?? 0 }}</strong></article>
    @endforeach
</section>
<section class="stats">
    <article class="stat"><span>全部賽事</span><strong>{{ $systemCounts['events'] }}</strong></article>
    <article class="stat"><span>開放報名</span><strong>{{ $systemCounts['open_events'] }}</strong></article>
    <article class="stat"><span>有效報名</span><strong>{{ $systemCounts['registrations'] }}</strong></article>
    <article class="stat"><span>會員</span><strong>{{ $systemCounts['members'] }}</strong></article>
</section>
<section class="panel"><div class="panel-heading"><h2>最近更新</h2><a href="{{ route('admin.news.index') }}">查看全部</a></div>
<table><thead><tr><th>標題</th><th>類別</th><th>狀態</th><th>更新時間</th></tr></thead><tbody>
@forelse($recentPosts as $post)<tr><td><a href="{{ route('admin.news.edit', $post) }}">{{ $post->title }}</a></td><td>{{ $post->sport->name }}</td><td><span class="badge {{ $post->status }}">{{ ['draft'=>'草稿','scheduled'=>'排程','published'=>'已發布','archived'=>'已封存'][$post->status] ?? $post->status }}</span></td><td>{{ $post->updated_at->format('Y-m-d H:i') }}</td></tr>
@empty<tr><td colspan="4" class="empty">尚無消息，請先新增第一篇內容。</td></tr>@endforelse
</tbody></table></section>
@endsection
