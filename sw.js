// Service Worker - Permite instalação da PWA
// Este arquivo pode ser expandido para implementar cache strategies no futuro

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js').catch(err => {
        console.log('SW registration failed:', err);
    });
}