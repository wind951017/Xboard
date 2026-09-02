<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body{margin:0;background:#f6f7fb;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"Microsoft YaHei",sans-serif}
        .box{width:380px;max-width:calc(100% - 32px);margin:12vh auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:26px}
        h1{font-size:24px;margin:0 0 20px}
        label{display:block;font-size:13px;color:#4b5563;margin:12px 0 6px}
        input{box-sizing:border-box;width:100%;padding:11px;border:1px solid #d1d5db;border-radius:6px}
        button{width:100%;margin-top:18px;background:#2563eb;color:#fff;border:0;border-radius:6px;padding:11px;cursor:pointer}
        .err{padding:10px 12px;background:#fef2f2;color:#b91c1c;border-radius:6px;margin-bottom:14px}
        .muted{color:#6b7280;font-size:14px}
    </style>
</head>
<body>
<form class="box" method="post" action="/agent/master/login">
    <h1>总代管理登录</h1>
    @if(!$configured)<div class="err">还没有设置管理密钥，请先执行 php artisan agent:master-key</div>@endif
    @if($error)<div class="err">{{ $error }}</div>@endif
    <label>管理密钥</label>
    <input name="password" type="password" required>
    <button type="submit">登录</button>
    <p class="muted">这个入口用于你创建代理、设置比例和结算佣金。</p>
</form>
</body>
</html>
