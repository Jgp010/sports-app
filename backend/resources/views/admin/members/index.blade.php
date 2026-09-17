@extends('admin.layout')
@section('title', '會員管理｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MEMBERS</p><h1>會員管理</h1></div></div>
<section class="panel"><form class="filters" method="get"><label class="filter-field wide">會員資料<input name="q" value="{{ request('q') }}" placeholder="姓名、Email 或電話"></label><button class="button">搜尋</button></form>
<table><thead><tr><th>姓名</th><th>聯絡資料</th><th>角色</th><th>SSO</th><th>報名數</th><th>狀態</th><th>建立時間</th><th>操作</th></tr></thead><tbody>
@forelse($members as $member)<tr><td>{{ $member->name }}</td><td>{{ $member->email }}<small>{{ $member->phone ?: '—' }}</small></td><td>{{ ['member'=>'一般會員','admin'=>'管理員','editor'=>'編輯人員'][$member->role] ?? $member->role }}</td><td>{{ $member->sso_provider ?: '—' }}</td><td>{{ $member->event_registrations_count }}</td><td>{{ $member->is_active?'啟用':'停用' }}</td><td>{{ $member->created_at->format('Y-m-d H:i') }}</td><td><a href="{{ route('admin.members.edit',$member) }}">查閱／編輯</a></td></tr>@empty<tr><td colspan="8" class="empty">尚無會員。</td></tr>@endforelse
</tbody></table></section>
@endsection
