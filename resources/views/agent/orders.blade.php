@extends('agent.layout')
@section('title', '订单记录')
@section('brand', $agent->site_name ?: $agent->name)
@section('nav') @include('agent.partials-agent-nav') @endsection
@section('heading', '订单记录')
@section('content')
<table>
    <thead><tr><th>订单号</th><th>用户</th><th>套餐</th><th>金额</th><th>状态</th><th>佣金</th><th>时间</th></tr></thead>
    <tbody>
    @foreach($orders as $order)
        <tr>
            <td>{{ $order->trade_no }}</td>
            <td>{{ $order->user?->email }}</td>
            <td>{{ $order->plan?->name }}</td>
            <td>{{ number_format(($order->total_amount ?? 0) / 100, 2) }}</td>
            <td>{{ \App\Models\Order::$statusMap[$order->status] ?? $order->status }}</td>
            <td>{{ number_format(($order->agent_commission_amount ?? 0) / 100, 2) }}</td>
            <td>{{ $order->created_at ? date('Y-m-d H:i', $order->created_at) : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $orders->links() }}
@endsection
