@extends('admin.layout')
@section('title', '賽事管理｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">EVENTS</p><h1>賽事管理</h1></div><a class="button primary" href="{{ route('admin.events.create') }}">新增賽事</a></div>
<section class="panel">
<form class="filters" method="get"><label class="filter-field">賽事名稱<input name="q" value="{{ request('q') }}" placeholder="輸入賽事名稱"></label><label class="filter-field">狀態<select name="status"><option value="">全部狀態</option>@foreach(['draft'=>'草稿','published'=>'已發布','cancelled'=>'已取消'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></label><button class="button">搜尋</button></form>
<table><thead><tr><th>賽事</th><th>日期／地點</th><th>報名期間</th><th>名額</th><th>狀態</th><th>操作</th></tr></thead><tbody>
@forelse($events as $event)<tr><td><strong>{{ $event->title }}</strong><small>{{ $event->sport->name }}</small></td><td>{{ $event->event_start_at->format('Y-m-d H:i') }}<small>{{ $event->venue }}</small></td><td>{{ $event->registration_open_at->format('Y-m-d H:i') }}<small>至 {{ $event->registration_close_at->format('Y-m-d H:i') }}</small></td><td>{{ $event->registrations_count }} / {{ $event->capacity ?? '不限' }}</td><td><span class="badge {{ $event->status }}">{{ ['draft'=>'草稿','published'=>'已發布','cancelled'=>'已取消'][$event->status] ?? $event->status }}</span></td><td class="actions"><a href="{{ route('admin.events.edit',$event) }}">編輯</a><a href="{{ route('admin.registrations.index',['event_id'=>$event->id]) }}">報名</a><form method="post" action="{{ route('admin.events.destroy',$event) }}" onsubmit="return confirm('確定刪除此賽事？')">@csrf @method('DELETE')<button class="link-button danger">刪除</button></form></td></tr>
@empty<tr><td colspan="6" class="empty">尚無賽事。</td></tr>@endforelse
</tbody></table></section>
@endsection
