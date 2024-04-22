#!/bin/bash

if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    echo 'DB_DSN="mysql:host=db;dbname=yii2db_6g5T5YBscm"' > .env
    echo "DB_USER=yii2db_admin" >> .env
    echo "DB_PASSWORD=yii2db_admin" >> .env
    echo "PROXY=" >> .env
    echo "API_KEY=" >> .env
    echo "FRONTEND_PROXY=0" >> .env
fi

# 等待 MySQL 服务启动并且可以接受连接
echo "Waiting for MySQL to be fully operational..."
until mysql -h db -u yii2db_admin -pyii2db_admin -e "SELECT 1" > /dev/null 2>&1; do
    echo "MySQL is not operational yet. Waiting..."
    sleep 1
done
echo "MySQL is fully operational."

# 执行 yii 迁移
php /app/yii migrate --interactive=0

exec "$@"