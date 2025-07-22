#!/bin/bash

echo "🚨 Git履歴からAPIキーを完全に削除します"
echo "⚠️  この操作は履歴を書き換えるため、慎重に実行してください"

# バックアップの作成
echo "📦 現在のレポジトリをバックアップ中..."
cp -r .git .git.backup.$(date +%Y%m%d_%H%M%S)

# BFG Repo-Cleanerを使用してAPIキーを削除
echo "🧹 git-filter-repoでAPIキーを削除中..."

# search.jsファイルからAPIキーパターンを削除
git filter-repo --replace-text <(echo 'AIzaSyDQrPePZ7hq3lOlqx3WWUqU7aQq3iqw3g8==>REMOVED_API_KEY') --force

echo "✅ 履歴の書き換え完了"
echo "🔄 強制プッシュが必要です: git push --force-with-lease origin main"

# 注意事項
echo ""
echo "⚠️  重要な注意事項:"
echo "1. Google Cloud ConsoleでAPIキーを無効化してください"
echo "2. 新しいAPIキーを生成してください" 
echo "3. .envファイルに新しいAPIキーを設定してください"
echo "4. 共同作業者がいる場合は事前に通知してください"