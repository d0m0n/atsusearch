/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.html",
  ],
  theme: {
    extend: {
      colors: {
        // デジタル庁デザインシステム（DADS）カラートークン
        dads: {
          // テキスト
          text: '#1A1A1C',
          'text-sub': '#626264',
          // 背景
          bg: '#FFFFFF',
          'bg-section': '#F1F1F4',
          // プライマリ
          primary: '#0017C1',
          'primary-hover': '#001499',
          // ボーダー
          border: '#D9D9DB',
          // ステータス
          error: '#EC0000',
          success: '#259D63',
        },
        // WBGT危険度レベル（環境省・CLAUDE.md仕様準拠）
        wbgt: {
          danger: '#D32F2F',        // 危険（31以上）
          'severe-warning': '#F57C00', // 厳重警戒（28〜31）
          warning: '#FBC02D',       // 警戒（25〜28）
          caution: '#0288D1',       // 注意（21〜25）
          safe: '#388E3C',          // ほぼ安全（21未満）
        },
      },
      fontFamily: {
        sans: ['"Noto Sans JP"', '"Hiragino Sans"', '"Hiragino Kaku Gothic ProN"', 'sans-serif'],
      },
      borderRadius: {
        sm: '4px',   // 小要素
        DEFAULT: '4px',
        md: '8px',   // カード
        lg: '12px',  // モーダル
      },
      spacing: {
        // 8pxグリッド（DADSの余白単位）
        // Tailwindのデフォルト（2=8px, 4=16px等）を補完
      },
      transitionTimingFunction: {
        'dads-page': 'cubic-bezier(0, 0, 0.2, 1)', // ease-out
        'dads-button': 'cubic-bezier(0.4, 0, 0.6, 1)', // ease
      },
      transitionDuration: {
        'page': '200ms',
        'button': '150ms',
        'alert': '300ms',
      },
      animation: {
        'fade-in': 'fadeIn 200ms ease-out forwards',
        'slide-down': 'slideDown 300ms ease-out forwards',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-100%)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [],
}
