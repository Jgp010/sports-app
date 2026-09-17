@extends('admin.layout')
@section('title', ($account->exists?'編輯':'新增').'帳號｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCOUNT EDITOR</p><h1>{{ $account->exists?'編輯後台帳號':'新增後台帳號' }}</h1></div><a class="button" href="{{ route('admin.accounts.index') }}">返回列表</a></div>
<form class="panel stack" method="post" action="{{ $account->exists?route('admin.accounts.update',$account):route('admin.accounts.store') }}">@csrf @if($account->exists)@method('PUT')@endif
<div class="form-grid"><label>登入帳號<input name="username" value="{{ old('username',$account->username) }}" minlength="3" maxlength="60" required autocomplete="off"></label><label>顯示名稱<input name="name" value="{{ old('name',$account->name) }}" maxlength="80" required></label></div>
<div class="form-grid"><label>權限<select name="role" required>@foreach(['admin'=>'系統管理員','editor'=>'後台編輯人員'] as $key=>$label)<option value="{{ $key }}" @selected(old('role',$account->role?:'editor')===$key)>{{ $label }}</option>@endforeach</select></label><label>密碼{{ $account->exists?'（留空表示不修改）':'' }}<input type="password" name="password" minlength="8" @required(!$account->exists) autocomplete="new-password"></label></div>
<label class="check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$account->exists?$account->is_active:true)) @disabled($account->is(auth()->user()))>啟用帳號</label>
<p class="muted">後台帳號不需要綁定電子郵件。登入時使用上方帳號及密碼。</p><button class="button primary">儲存帳號</button></form>
@endsection
