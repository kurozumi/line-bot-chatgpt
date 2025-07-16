# LINE Bot × ChatGPT Integration Guide

## プロジェクト概要
SymfonyフレームワークとLINE Messaging APIを使用したチャットボットアプリケーション。
ChatGPT (OpenAI) による自動応答機能、非同期メッセージ処理とMonologによるログ機能を実装。

## アーキテクチャ構成

### 主要コンポーネント

1. **Webhook処理**
   - `src/Webhook/LineRequestParser.php` - LINE Webhookリクエストのパース
   - `src/RemoteEvent/LineWebhookConsumer.php` - Webhookイベントの消費・処理

2. **非同期メッセージ処理**
   - `src/Message/LineReplyMessage.php` - 返信メッセージのデータ構造
   - `src/MessageHandler/LineReplyMessageHandler.php` - 非同期返信処理
   - `src/Message/LinePushMessage.php` - Push Message用のデータ構造
   - `src/MessageHandler/LinePushMessageHandler.php` - Push Message送信処理
   - `src/Message/ChatGptResponseMessage.php` - ChatGPT応答生成用のデータ構造
   - `src/MessageHandler/ChatGptResponseMessageHandler.php` - ChatGPT応答の非同期処理

3. **AI応答機能**
   - `src/Service/ChatGptService.php` - ChatGPT API連携サービス

4. **設定ファイル**
   - `config/packages/messenger.yaml` - メッセンジャー設定
   - `config/packages/monolog.yaml` - ログ設定
   - `config/packages/webhook.yaml` - Webhook設定
   - `config/packages/chatgpt.yaml` - ChatGPT API設定

## 環境設定

### 必須環境変数 (.env)
```env
LINE_ACCESS_TOKEN=your_line_access_token_here
OPENAI_API_KEY=your_openai_api_key_here
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

### 依存関係
- symfony/framework-bundle
- symfony/monolog-bundle
- symfony/messenger
- symfony/doctrine-messenger
- symfony/orm-pack
- symfony/webhook
- linecorp/line-bot-sdk
- openai-php/client

## 開発・運用コマンド

### 開発環境セットアップ
```bash
# 依存関係インストール
composer install

# データベースセットアップ
php bin/console messenger:setup-transports
```

### 非同期メッセージ処理
```bash
# メッセージワーカー起動（本番環境で必須）
php bin/console messenger:consume async -vv

# 開発時のテスト（5件処理で終了）
php bin/console messenger:consume async -vv --limit=5
```

### ログ確認
```bash
# 開発環境のログ確認
tail -f var/log/dev.log

# エラーログのみ確認
grep ERROR var/log/dev.log
```

## アプリケーションの動作フロー

1. **Webhookリクエスト受信**
   - LINE Platform → `/webhook/line` エンドポイント
   - `LineRequestParser` でリクエストを検証・パース

2. **イベント処理**
   - `LineWebhookConsumer` でメッセージイベントを処理
   - 即座に「考え中...」メッセージを返信（Reply Message）
   - ChatGPT応答メッセージをキューに追加

3. **非同期AI応答生成**
   - `ChatGptResponseMessageHandler` が非同期でChatGPT処理を実行
   - 3秒の待機時間でより自然な応答タイミングを実現
   - `ChatGptService` でOpenAI APIを呼び出し

4. **3段階の返信処理**
   - **即座の返信**: `LineReplyMessage` でReply Tokenを使用した即座の返信
   - **待機時間**: 3秒間の待機でより自然な応答タイミング
   - **実際の回答**: `LinePushMessage` でPush Messageによる実際の回答送信

## 実装されている機能

### 1. Webhook処理
- LINE Platformからのリクエスト検証
- 署名検証による不正リクエストの拒否
- JSON形式のメッセージパース

### 2. 非同期メッセージ処理
- Symfony Messengerによる非同期処理
- Doctrineトランスポートによるメッセージキュー
- 失敗時の再試行機能

### 3. AI応答機能
- ChatGPT (GPT-3.5-turbo) による自動応答
- 日本語対応のシステムプロンプト
- エラー時のフォールバック機能
- 設定ファイルによるモデル・パラメータ管理

### 4. 3段階応答システム
- **即座の応答**: Reply Messageによる「考え中...」メッセージ
- **待機時間**: 3秒間の待機でより自然な応答タイミング
- **実際の回答**: Push Messageによる ChatGPT応答の送信
- ユーザーの待機時間を短縮し、UXを向上

### 5. ログ機能
- Monologによる構造化ログ
- 環境別ログレベル設定
- リクエスト/レスポンスの詳細ログ

## 開発時の注意点

### セキュリティ
- `LINE_ACCESS_TOKEN` は絶対に外部に漏らさない
- `OPENAI_API_KEY` の管理も同様に重要
- 本番環境では`.env.local`で環境変数を設定
- 署名検証は必須（`LineRequestParser`で実装済み）

### パフォーマンス
- メッセージ処理は非同期で実行
- 本番環境では複数のワーカープロセスを推奨
- データベースはSQLiteから本番用DBへ変更を検討

### エラーハンドリング
- 全ての例外はログに記録
- LINE APIエラーの適切な処理
- メッセージ処理失敗時の再試行

## トラブルシューティング

### よくある問題

1. **メッセージが返信されない**
   - ワーカーが起動しているか確認
   - `LINE_ACCESS_TOKEN`の設定確認
   - ログでエラーメッセージを確認

2. **署名検証エラー**
   - Webhook URLの設定確認
   - LINE_ACCESS_TOKENの正確性確認
   - リクエストヘッダーの確認

3. **データベース接続エラー**
   - `DATABASE_URL`の設定確認
   - SQLiteファイルの権限確認
   - `messenger:setup-transports`の実行確認

4. **ChatGPT応答エラー**
   - `OPENAI_API_KEY`の設定確認
   - OpenAI APIの利用制限確認
   - ログでAPIエラーメッセージを確認
   - `config/packages/chatgpt.yaml`の設定確認

### デバッグコマンド
```bash
# メッセージキューの状態確認
php bin/console messenger:stats

# 失敗したメッセージの確認
php bin/console messenger:failed:show

# キャッシュクリア
php bin/console cache:clear
```

## ChatGPT設定ファイル

`config/packages/chatgpt.yaml`でChatGPTの動作をカスタマイズできます。

```yaml
parameters:
    chatgpt:
        model: 'gpt-3.5-turbo'
        max_tokens: 1000
        temperature: 0.7
        system_message: 'あなたは親切で丁寧な日本語のアシスタントです。簡潔で分かりやすい返答を心がけてください。'
        error_messages:
            payment_required: 'OpenAIアカウントの支払い情報を確認してください。'
            general_error: 'すみません、一時的に返答できません。しばらく経ってから再度お試しください。'
```

### 設定項目
- `model`: 使用するChatGPTモデル（gpt-3.5-turbo、gpt-4等）
- `max_tokens`: 最大トークン数
- `temperature`: 応答の創造性（0.0-2.0）
- `system_message`: システムプロンプト
- `error_messages`: エラー時の返信メッセージ

## 今後の拡張予定

### 機能拡張
- 画像・スタンプメッセージ対応
- リッチメニュー機能
- ユーザーデータ管理
- 会話履歴の保存
- 待機メッセージのカスタマイズ機能

### 技術的改善
- Redis/RabbitMQによる本格的なメッセージキュー
- Docker環境での運用
- CI/CDパイプライン構築
- 監視・メトリクス収集

---

## 開発者向けメモ

### 新機能追加時の手順
1. 適切なnamespaceでクラス作成
2. 必要に応じてメッセージ/ハンドラー追加
3. テストケース作成
4. この文書を更新

### コードスタイル
- PSR-12準拠
- 型宣言の徹底
- 例外処理の適切な実装
- ログの構造化

## 使用方法

### 基本的な使用方法
1. LINEでメッセージを送信
2. 即座に「考え中... 少々お待ちください 🤔」が返信される
3. ChatGPTが応答を生成（数秒～数十秒）
4. 実際の回答がPush Messageで送信される

### メッセージの流れ
```
ユーザー → [メッセージ送信]
        ↓
チャットボット → [即座] "考え中... 少々お待ちください 🤔"
        ↓
ChatGPT → [応答生成]
        ↓
チャットボット → [Push Message] "実際の回答"
```

最終更新日: 2025-07-16