@extends('agent.layout')
@section('title', '佣金结算')
@section('brand', '总代管理')
@section('nav') @include('agent.partials-master-nav') @endsection
@section('heading', '佣金结算')
@section('content')
@if($saved)<div class="ok">已结算</div>@endif
<form class="card" method="post" action="/agent/master/commissions/settle">
    <label>要结算的佣金记录 ID，多个用英文逗号分隔</label>
    <input name="ids" placeholder="1,2,3">
    <button type="submit">标记为已结算</button>
</form>
<table>
    <thead><tr><th>ID</th><th>代理</th><th>用户</th><th>订单</th><th>订单金额</th><th>比例</th><th>佣金</th><th>状态</th><th>时间</th></tr></thead>
    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{{ $log->id }}</td>
            <td>{{ $log->agent?->name }}</td>
            <td>{{ $log->user?->email }}</td>
            <td>{{ $log->trade_no }}</td>
            <td>{{ number_format($log->order_amount / 100, 2) }}</td>
            <td>{{ $log->commission_rate }}%</td>
            <td>{{ number_format($log->commission_amount / 100, 2) }}</td>
            <td>{{ \App\Models\AgentCommissionLog::$statusMap[$log->status] ?? $log->status }}</td>
            <td>{{ $log->created_at ? date('Y-m-d H:i', $log->created_at) : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $logs->links() }}
@endsection
