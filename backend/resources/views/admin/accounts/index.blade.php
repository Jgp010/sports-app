@extends('admin.layout')
@section('title', '帳號管理｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCOUNTS</p><h1>帳號管理</h1></div><a class="button primary" href="{{ route('admin.accounts.create') }}">新增帳號</a></div>
<section class="panel"><form class="filters" method="get"><label class="filter-field wide">帳號或名稱<input name="q" value="{{ request('q') }}" placeholder="輸入帳號或顯示名稱"></label><button class="button">搜尋</button></form>
<table><thead><tr><th>帳號</th><th>顯示名稱</th><th>權限</th><th>狀態</th><th>最後登入</th><th>操作</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><strong>{{ $account->username }}</strong></td><td>{{ $account->name }}</td><td>{{ $account->role==='admin'?'系統管理員':'後台編輯人員' }}</td><td><span class="badge {{ $account->is_active?'published':'cancelled' }}">{{ $account->is_active?'啟用':'停用' }}</span></td><td>{{ $account->last_login_at?->format('Y-m-d H:i') ?? '尚未登入' }}</td><td class="actions"><a href="{{ route('admin.accounts.edit',$account) }}">編輯</a>@if(!$account->is(auth()->user()))<form method="post" action="{{ route('admin.accounts.destroy',$account) }}" onsubmit="return confirm('確定刪除此後台帳號？')">@csrf @method('DELETE')<button class="link-button danger">刪除</button></form>@endif</td></tr>
@empty<tr><td colspan="6" class="empty">尚無後台帳號。</td></tr>@endforelse
</tbody></table>{{ $accounts->links() }}</section>
@endsection
