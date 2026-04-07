console.log('Simple search.js loaded')

// DOM読み込み完了後に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded')
    
    const appElement = document.getElementById('atsu-search-app')
    if (appElement) {
        appElement.innerHTML = '<p>アプリケーションは正常に読み込まれました。現在、単純化されたバージョンを実行しています。</p>'
    }
})