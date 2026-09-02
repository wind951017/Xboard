<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AgentMasterKey extends Command
{
    protected $signature = 'agent:master-key {key? : 总代管理密钥，留空则自动生成}';

    protected $description = 'Set master key for the agent management panel';

    public function handle(): int
    {
        $key = (string) ($this->argument('key') ?: Str::random(18));
        admin_setting(['agent_master_key' => $key]);

        $this->info('总代管理密钥已设置');
        $this->line('密钥: '.$key);
        $this->line('总代入口: /agent/master/login');

        return self::SUCCESS;
    }
}
