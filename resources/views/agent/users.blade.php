@extends('agent.layout')
@section('title', '我的用户')
@section('brand', $agent->site_name ?: $agent->name)
@section('nav') @include('agent.partials-agent-nav') @endsection
@section('heading', '我的用户')
@section('content')
<table>
    <thead><tr><th>ID</th><th>邮箱</th><th>套餐</th><th>余额</th><th>注册时间</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->plan_id ?: '-' }}</td>
            <td>{{ number_format(($user->balance ?? 0) / 100, 2) }}</td>
            <td>{{ $user->created_at ? date('Y-m-d H:i', $user->created_at) : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
