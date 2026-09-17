@extends('admin.layout')
@section('title', ($event->exists?'編輯':'新增').'賽事｜管理後台')
@section('content')
@php
$formItems = old('items', $event->exists
    ? $event->items->map(fn($item) => [
        'id'=>$item->id, 'name'=>$item->name, 'description'=>$item->description,
        'registration_fee'=>$item->registration_fee, 'early_bird_fee'=>$item->early_bird_fee,
        'early_bird_ends_at'=>$item->early_bird_ends_at?->format('Y-m-d\TH:i'), 'is_active'=>$item->is_active ? 1 : 0,
    ])->all()
    : [['name'=>'', 'description'=>'', 'registration_fee'=>'0', 'early_bird_fee'=>'', 'early_bird_ends_at'=>'', 'is_active'=>1]]);
@endphp
<div class="page-heading"><div><p class="eyebrow">EVENT EDITOR</p><h1>{{ $event->exists?'編輯賽事':'新增賽事' }}</h1></div><a class="button" href="{{ route('admin.events.index') }}">返回列表</a></div>
<form method="post" action="{{ $event->exists?route('admin.events.update',$event):route('admin.events.store') }}" class="panel stack">@csrf @if($event->exists)@method('PUT')@endif
<div class="form-grid"><label>運動類別<select name="sport_id" required>@foreach($sports as $sport)<option value="{{ $sport->id }}" @selected(old('sport_id',$event->sport_id)==$sport->id)>{{ $sport->name }}</option>@endforeach</select></label><label>狀態<select name="status" required>@foreach(['draft'=>'草稿','published'=>'已發布','cancelled'=>'已取消'] as $key=>$label)<option value="{{ $key }}" @selected(old('status',$event->status?:'draft')===$key)>{{ $label }}</option>@endforeach</select></label></div>
<label>賽事名稱<input name="title" value="{{ old('title',$event->title) }}" maxlength="150" required></label>
<label>賽事說明<textarea name="description" rows="8" required>{{ old('description',$event->description) }}</textarea></label><label>地點<input name="venue" value="{{ old('venue',$event->venue) }}" required></label>
<div class="form-grid"><label>開始時間（台灣）<input type="datetime-local" name="event_start_at" value="{{ old('event_start_at',$event->event_start_at?->format('Y-m-d\TH:i')) }}" required></label><label>結束時間（台灣）<input type="datetime-local" name="event_end_at" value="{{ old('event_end_at',$event->event_end_at?->format('Y-m-d\TH:i')) }}"></label></div>
<div class="form-grid"><label>報名開放（台灣）<input type="datetime-local" name="registration_open_at" value="{{ old('registration_open_at',$event->registration_open_at?->format('Y-m-d\TH:i')) }}" required></label><label>報名截止（台灣）<input type="datetime-local" name="registration_close_at" value="{{ old('registration_close_at',$event->registration_close_at?->format('Y-m-d\TH:i')) }}" required></label></div>
<label>名額（留空表示不限）<input type="number" min="1" name="capacity" value="{{ old('capacity',$event->capacity) }}"></label>
<section class="item-editor"><div class="panel-heading"><div><h2>賽事項目</h2><p class="muted">可建立跳馬、彈翻床等多個項目，並分別設定一般與早鳥費用。</p></div><button class="button" type="button" id="add-item">新增項目</button></div><div id="event-items">
@foreach($formItems as $index=>$item)
<article class="event-item-row"><input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}"><div class="item-row-heading"><strong>項目 <span class="item-number">{{ $index+1 }}</span></strong><button type="button" class="link-button danger remove-item">移除</button></div>
<div class="form-grid"><label>項目名稱<input name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" required maxlength="120"></label><label>項目說明<input name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="500"></label></div>
<div class="item-price-grid"><label>報名金額（元）<input type="number" name="items[{{ $index }}][registration_fee]" value="{{ $item['registration_fee'] ?? 0 }}" min="0" step="1" required></label><label>早鳥金額（元）<input type="number" name="items[{{ $index }}][early_bird_fee]" value="{{ $item['early_bird_fee'] ?? '' }}" min="0" step="1"></label><label>早鳥截止（台灣）<input type="datetime-local" name="items[{{ $index }}][early_bird_ends_at]" value="{{ $item['early_bird_ends_at'] ?? '' }}"></label></div>
<label class="check"><input type="hidden" name="items[{{ $index }}][is_active]" value="0"><input type="checkbox" name="items[{{ $index }}][is_active]" value="1" @checked((bool)($item['is_active'] ?? true))>開放此項目報名</label></article>
@endforeach
</div></section><button class="button primary">儲存賽事</button></form>
<template id="event-item-template"><article class="event-item-row"><input type="hidden" data-name="id"><div class="item-row-heading"><strong>項目 <span class="item-number"></span></strong><button type="button" class="link-button danger remove-item">移除</button></div><div class="form-grid"><label>項目名稱<input data-name="name" required maxlength="120"></label><label>項目說明<input data-name="description" maxlength="500"></label></div><div class="item-price-grid"><label>報名金額（元）<input type="number" data-name="registration_fee" value="0" min="0" step="1" required></label><label>早鳥金額（元）<input type="number" data-name="early_bird_fee" min="0" step="1"></label><label>早鳥截止（台灣）<input type="datetime-local" data-name="early_bird_ends_at"></label></div><label class="check"><input type="hidden" data-name="is_active" value="0"><input type="checkbox" data-name="is_active" value="1" checked>開放此項目報名</label></article></template>
<script>
(()=>{const container=document.getElementById('event-items'),template=document.getElementById('event-item-template');const reindex=()=>[...container.children].forEach((row,index)=>{row.querySelector('.item-number').textContent=index+1;row.querySelectorAll('[name],[data-name]').forEach(input=>{const field=input.dataset.name||input.name.match(/\[([^\]]+)\]$/)?.[1];if(field)input.name=`items[${index}][${field}]`;});});document.getElementById('add-item').addEventListener('click',()=>{container.append(template.content.cloneNode(true));reindex();});container.addEventListener('click',event=>{if(event.target.classList.contains('remove-item')){if(container.children.length===1){alert('至少需要一個賽事項目。');return;}event.target.closest('.event-item-row').remove();reindex();}});reindex();})();
</script>
@endsection
