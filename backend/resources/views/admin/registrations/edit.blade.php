@extends('admin.layout')
@section('title', '編輯報名｜管理後台')
@section('content')
<div class="page-heading"><div><p class="eyebrow">REGISTRATION</p><h1>{{ $registration->registration_no }}</h1><p>{{ $registration->event->title }}｜{{ $registration->user->name }}（{{ $registration->user->email }}）</p></div><a class="button" href="{{ route('admin.registrations.index') }}">返回列表</a></div>
<form method="post" action="{{ route('admin.registrations.update',$registration) }}" class="panel stack">@csrf @method('PUT')
<label>狀態<select name="status">@foreach(['registered'=>'已報名','cancelled'=>'已取消','attended'=>'已出席'] as $key=>$label)<option value="{{ $key }}" @selected(old('status',$registration->status)===$key)>{{ $label }}</option>@endforeach</select></label>
<div class="form-grid"><label>聯絡電話<input name="contact_phone" value="{{ old('contact_phone',$registration->contact_phone) }}" required></label><label>單位<input name="organization" value="{{ old('organization',$registration->organization) }}" required></label></div><div class="form-grid"><label>緊急聯絡人<input name="emergency_contact_name" value="{{ old('emergency_contact_name',$registration->emergency_contact_name) }}" required></label><label>緊急聯絡電話<input name="emergency_contact_phone" value="{{ old('emergency_contact_phone',$registration->emergency_contact_phone) }}" required></label></div>
<fieldset class="choice-list"><legend>報名項目</legend>@foreach($registration->event->items as $eventItem)<label class="check"><input type="checkbox" name="item_ids[]" value="{{ $eventItem->id }}" @checked(in_array($eventItem->id, old('item_ids',$registration->items->pluck('id')->all())))>{{ $eventItem->name }}（NT$ {{ number_format((float)$eventItem->currentPrice()) }}）</label>@endforeach</fieldset>
<p><strong>目前總額：</strong>NT$ {{ number_format((float)$registration->total_amount) }}</p>
<label>會員備註<textarea name="notes" rows="4">{{ old('notes',$registration->notes) }}</textarea></label><label>後台備註<textarea name="admin_notes" rows="4">{{ old('admin_notes',$registration->admin_notes) }}</textarea></label><button class="button primary">儲存報名資料</button></form>
@endsection
