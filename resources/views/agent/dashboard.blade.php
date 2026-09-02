@extends('agent.layout')
@section('title', '代理概览')
@section('brand', $agent->site_name ?: $agent->name)
@section('nav') @include('agent.partials-agent-nav') @endsection
@section('heading', '代理概览')
@section('content')
<div class="grid">
    <div class="metric">用户数<strong>{{ $stats['users'] }}</strong></div>
    <div class="metric">订单数<strong>{{ $stats['orders'] }}</strong></div>
    <div class="metric">成交金额<strong>{{ number_format($stats['paid_amount'] / 100, 2) }}</strong></div>
    <div class="metric">待结算佣金<strong>{{ number_format($stats['pending_commission'] / 100, 2) }}</strong></div>
</div>
<div class="card">
    <p>佣金比例：{{ $agent->commission_rate }}%</p>
    <p>绑定域名：{{ $agent->domain ?: '未设置' }}</p>
    <p>客服信息：{{ $agent->contact ?: '未设置' }}</p>
</div>
@endsection
