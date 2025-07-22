import { createApp } from 'vue'
import UserLocationHistory from './components/UserLocationHistory.vue'
import axios from 'axios'

// Axiosの設定
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// CSRFトークンの設定
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
} else {
  console.error('CSRF token not found')
}

const app = createApp({})
app.component('UserLocationHistory', UserLocationHistory)
app.mount('#history-app')