import { createApp } from 'vue';

const app = createApp({
    data() {
        return {
            message: 'AtsuSearch - 熱中症対策アプリ',
            subtitle: 'Laravel 12 + Vue 3 + Tailwind CSS'
        };
    },
    template: `
        <div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
                <h1 class="text-3xl font-bold text-gray-800 mb-4 text-center">
                    🌡️ {{ message }}
                </h1>
                <p class="text-gray-600 text-center mb-6">{{ subtitle }}</p>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-green-100 rounded-lg">
                        <span class="text-green-800 font-medium">システム状態</span>
                        <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm">正常</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-blue-100 rounded-lg">
                        <span class="text-blue-800 font-medium">Vue.js</span>
                        <span class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm">動作中</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-purple-100 rounded-lg">
                        <span class="text-purple-800 font-medium">Tailwind CSS</span>
                        <span class="px-3 py-1 bg-purple-500 text-white rounded-full text-sm">適用済み</span>
                    </div>
                </div>
                
                <button class="w-full mt-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-300 font-medium">
                    AtsuSearchを開始
                </button>
            </div>
        </div>
    `
});

app.mount('#app');
