# SSL証明書の設定

HTTPS対応のためのSSL証明書を配置するディレクトリです。

## 開発環境での自己署名証明書の作成

```bash
# 証明書とキーの生成
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/server.key \
  -out docker/nginx/ssl/server.crt \
  -subj "/C=JP/ST=Tokyo/L=Tokyo/O=Development/CN=localhost"

# 権限設定
chmod 600 docker/nginx/ssl/server.key
chmod 644 docker/nginx/ssl/server.crt
```

## 本番環境での証明書設定

本番環境では、Let's Encrypt等の信頼できる証明書を使用してください。

証明書ファイルを以下の名前で配置:
- `server.crt` - 証明書ファイル
- `server.key` - 秘密鍵ファイル

## Nginxの設定

HTTPS対応のためのNginx設定は、必要に応じて `nginx.conf` に追加してください。

```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    
    ssl_certificate /etc/nginx/ssl/server.crt;
    ssl_certificate_key /etc/nginx/ssl/server.key;
    
    # SSL設定
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # 既存の設定...
}
```