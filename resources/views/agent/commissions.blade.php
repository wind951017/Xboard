@extends('agent.layout')
@section('title', '佣金记录')
@section('brand', $agent->site_name ?: $agent->name)
@section('nav') @include('agent.partials-agent-nav') @endsection
@section('heading', '佣金记录')
@section('content')
<table>
    <thead><tr><th>订单号</th><th>用户</th><th>订单金额</th><th>比例</th><th>佣金</th><th>状态</th><th>结算时间</th></tr></thead>
    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{{ $log->trade_no }}</td>
            <td>{{ $log->user?->email }}</td>
            <td>{{ number_format($log->order_amount / 100, 2) }}</td>
            <td>{{ $log->commission_rate }}%</td>
            <td>{{ number_format($log->commission_amount / 100, 2) }}</td>
            <td>{{ \App\Models\AgentCommissionLog::$statusMap[$log->status] ?? $log->status }}</td>
            <td>{{ $log->settled_at ? date('Y-m-d H:i', $log->settled_at) : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $logs->links() }}
@endsection
