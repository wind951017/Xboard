@extends('agent.layout')
@section('title', '代理管理')
@section('brand', '总代管理')
@section('nav') @include('agent.partials-master-nav') @endsection
@section('heading', '代理管理')
@section('content')
@if(request('error'))<div class="err">{{ request('error') }}</div>@endif
@if($saved)<div class="ok">已保存</div>@endif
@if($password)<div class="ok">新代理初始密码：{{ $password }}</div>@endif
<div class="grid">
    <div class="metric">代理数<strong>{{ $stats['agents'] }}</strong></div>
    <div class="metric">代理用户<strong>{{ $stats['users'] }}</strong></div>
    <div class="metric">代理订单<strong>{{ $stats['orders'] }}</strong></div>
    <div class="metric">待结算<strong>{{ number_format($stats['pending_commission'] / 100, 2) }}</strong></div>
</div>
<form class="card" method="post" action="/agent/master/agents">
    <h2>创建代理</h2>
    <div class="form-grid">
        <div><label>代理名称</label><input name="name" required></div>
        <div><label>登录邮箱</label><input name="email" type="email" required></div>
        <div><label>绑定域名</label><input name="domain" placeholder="agent.example.com"></div>
        <div><label>佣金比例</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="30" required></div>
    </div>
    <button type="submit">创建代理</button>
</form>
<div class="card">
    <h2>代理列表</h2>
    @foreach($agents as $agent)
        <form method="post" action="/agent/master/agents/{{ $agent->id }}" style="border-top:1px solid #e5e7eb;padding-top:14px;margin-top:14px">
            <div class="form-grid">
                <div><label>名称</label><input name="name" value="{{ $agent->name }}" required></div>
                <div><label>邮箱</label><input name="email" value="{{ $agent->email }}" required></div>
                <div><label>主域名</label><input name="domain" value="{{ $agent->domain }}"></div>
                <div><label>佣金比例</label><input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $agent->commission_rate }}" required></div>
                <div><label>备用域名，逗号或换行分隔</label><textarea name="domains">{{ $agent->domains }}</textarea></div>
                <div><label>状态</label><select name="status"><option value="1" @selected($agent->status)>启用</option><option value="0" @selected(!$agent->status)>禁用</option></select></div>
                <div><label>重置密码，留空不改</label><input name="password" type="password"></div>
                <div><label>余额 / 已结算</label><input value="{{ number_format($agent->balance / 100, 2) }} / {{ number_format($agent->settled_amount / 100, 2) }}" disabled></div>
            </div>
            <button type="submit">保存</button>
        </form>
    @endforeach
</div>
{{ $agents->links() }}
@endsection
