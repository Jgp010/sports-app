<!doctype html>
<html lang="zh-Hant">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>管理後台登入</title><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body class="login-page">
<main class="login-card">
    <p class="eyebrow">SPORTS DESK</p>
    <h1>管理後台登入</h1>
    <p class="muted">管理賽事消息、發布排程與運動類別。</p>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('admin.login.store') }}" class="stack">@csrf
        <label>帳號<input name="username" value="{{ old('username') }}" required maxlength="60" autofocus autocomplete="username"></label>
        <label>密碼<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="button primary" type="submit">登入</button>
    </form>
</main>
</body>
</html>
