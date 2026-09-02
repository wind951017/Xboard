<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Services\AgentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentCreate extends Command
{
    protected $signature = 'agent:create
        {email : 代理登录邮箱}
        {--name= : 代理名称}
        {--password= : 代理登录密码}
        {--domain= : 代理绑定域名}
        {--rate=30 : 代理佣金比例}';

    protected $description = 'Create or update an agent account';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: Str::random(12));
        $name = (string) ($this->option('name') ?: explode('@', $email)[0]);
        $domain = app(AgentService::class)->normalizeHost($this->option('domain'));
        $rate = (float) $this->option('rate');

        $agent = Agent::updateOrCreate(
            ['email' => $email],
            [
                'code' => Agent::where('email', $email)->value('code') ?: Str::lower(Str::random(8)),
                'name' => $name,
                'password' => Hash::make($password),
                'domain' => $domain,
                'site_name' => $name,
                'commission_rate' => $rate,
                'status' => 1,
            ]
        );

        $this->info('代理已创建/更新');
        $this->line('ID: '.$agent->id);
        $this->line('代理编号: '.$agent->code);
        $this->line('邮箱: '.$email);
        $this->line('密码: '.$password);
        $this->line('佣金比例: '.$rate.'%');
        $this->line('代理后台: /agent/login');

        return self::SUCCESS;
    }
}
