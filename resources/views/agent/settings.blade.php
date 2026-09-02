@extends('agent.layout')
@section('title', '网站设置')
@section('brand', $agent->site_name ?: $agent->name)
@section('nav') @include('agent.partials-agent-nav') @endsection
@section('heading', '网站设置')
@section('content')
@if($saved)<div class="ok">已保存</div>@endif
@if($error)<div class="err">{{ $error }}</div>@endif
<form class="card" method="post" action="/agent/settings">
    <label>网站名称</label>
    <input name="site_name" value="{{ $agent->site_name }}">
    <label>Logo 地址</label>
    <input name="logo" value="{{ $agent->logo }}">
    <label>客服信息</label>
    <input name="contact" value="{{ $agent->contact }}">
    <label>新密码，不改请留空</label>
    <input name="password" type="password">
    <button type="submit">保存</button>
</form>
@endsection
