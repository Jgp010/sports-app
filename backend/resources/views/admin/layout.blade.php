<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '體育消息管理後台')</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-extra.css') }}">
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('admin.dashboard') }}">SPORTS DESK</a>
    <nav>
        <a href="{{ route('admin.dashboard') }}">儀表板</a>
        <a href="{{ route('admin.news.index') }}">賽事消息</a>
        <a href="{{ route('admin.events.index') }}">賽事</a>
        <a href="{{ route('admin.registrations.index') }}">報名</a>
        <a href="{{ route('admin.members.index') }}">會員</a>
        @if(auth()->user()?->role === 'admin')<a href="{{ route('admin.accounts.index') }}">帳號管理</a>@endif
        <a href="{{ route('admin.sports.index') }}">運動類別</a>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="link-button">登出</button></form>
    </nav>
</header>
<main class="container">
    <p class="timezone-note">系統時間：台灣時間（Asia/Taipei）</p>
    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert error"><strong>請修正以下問題：</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
