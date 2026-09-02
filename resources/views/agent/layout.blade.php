<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '代理后台')</title>
    <style>
        body{margin:0;background:#f6f7fb;color:#1f2937;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"Microsoft YaHei",sans-serif}
        a{color:#2563eb;text-decoration:none}
        .shell{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
        .side{background:#111827;color:#fff;padding:24px 18px}
        .brand{font-size:20px;font-weight:700;margin-bottom:28px}
        .nav a{display:block;color:#d1d5db;padding:10px 12px;border-radius:6px;margin-bottom:6px}
        .nav a:hover,.nav a.active{background:#1f2937;color:#fff}
        .main{padding:28px}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px;margin-bottom:18px}
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px}
        .metric strong{display:block;font-size:24px;margin-top:8px}
        table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden}
        th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px}
        th{background:#f9fafb;color:#4b5563}
        input,textarea,select{box-sizing:border-box;width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px}
        label{display:block;font-size:13px;color:#4b5563;margin:12px 0 6px}
        button,.btn{display:inline-block;background:#2563eb;color:#fff;border:0;border-radius:6px;padding:10px 14px;cursor:pointer}
        .danger{background:#dc2626}
        .muted{color:#6b7280}
        .ok{padding:10px 12px;background:#ecfdf5;color:#047857;border-radius:6px;margin-bottom:14px}
        .err{padding:10px 12px;background:#fef2f2;color:#b91c1c;border-radius:6px;margin-bottom:14px}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        @media(max-width:900px){.shell{grid-template-columns:1fr}.side{position:static}.grid,.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <div class="brand">@yield('brand', '代理后台')</div>
        <nav class="nav">
            @yield('nav')
        </nav>
    </aside>
    <main class="main">
        <div class="top">
            <h1>@yield('heading')</h1>
            @yield('actions')
        </div>
        @yield('content')
    </main>
</div>
</body>
</html>
