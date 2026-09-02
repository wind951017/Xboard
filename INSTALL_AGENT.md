# Xboard 代理模式安装说明

仓库：https://github.com/wind951017/Xboard

成品镜像：

```bash
ghcr.io/wind951017/xboard:latest
推荐系统
Ubuntu 24.04 LTS
一键安装
apt update -y
apt install -y ca-certificates curl git ufw

curl -fsSL https://get.docker.com | sh
systemctl enable --now docker

ufw allow 22/tcp
ufw allow 80/tcp
ufw --force enable

cd ~
rm -rf Xboard
mkdir -p Xboard
cd Xboard

mkdir -p .docker/.data storage/logs storage/theme plugins
touch .env

cat > compose.yaml <<'EOF'
services:
  xboard:
    image: ghcr.io/wind951017/xboard:latest
    restart: unless-stopped
    ports:
      - "80:7001"
    volumes:
      - ./.env:/www/.env
      - ./.docker/.data/:/www/.docker/.data
      - ./storage/logs:/www/storage/logs
      - ./storage/theme:/www/storage/theme
      - ./plugins:/www/plugins
      - redis-data:/data
    environment:
      - RESOURCE_PROFILE=balanced
      - ENABLE_HORIZON=true
      - ENABLE_CADDY=false
      - ENABLE_WEB=true
      - ENABLE_REDIS=true
      - ENABLE_WS_SERVER=true
      - docker=true

volumes:
  redis-data:
EOF

docker compose pull
docker compose run --rm xboard env \
  ENABLE_SQLITE=true \
  ENABLE_REDIS=true \
  ADMIN_ACCOUNT=admin@demo.com \
  php artisan xboard:install

docker compose up -d --force-recreate

sleep 5

docker compose exec xboard env CACHE_DRIVER=array QUEUE_CONNECTION=sync SESSION_DRIVER=array php artisan migrate --force

docker compose exec xboard env CACHE_DRIVER=array QUEUE_CONNECTION=sync SESSION_DRIVER=array php artisan agent:master-key myagent123456

docker compose ps



访问地址
主站：
http://你的域名
总代理后台：
http://你的域名/agent/master/login
代理后台：
http://代理域名/agent/login
创建代理
cd ~/Xboard

docker compose exec xboard env CACHE_DRIVER=array QUEUE_CONNECTION=sync SESSION_DRIVER=array php artisan agent:create agent1@example.com \
  --name=代理A \
  --domain=agent1.yourdomain.com \
  --rate=30
说明：
- --domain 填代理自己的域名，不要带 http://
- --rate=30 表示佣金比例 30%
- 用户从代理域名注册后，会自动归属这个代理
- 支付接口、节点、套餐都走主站
- 代理后台只看自己的用户、订单、佣金

5. 页面底部提交说明填：

```text
Add agent install guide







