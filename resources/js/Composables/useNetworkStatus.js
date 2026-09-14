import { ref, onMounted, onUnmounted } from 'vue';

export function useNetworkStatus() {
    // Check if navigator is defined (for SSR safety, though this is mostly client side)
    const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
    };

    onMounted(() => {
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
    });

    onUnmounted(() => {
        window.removeEventListener('online', updateOnlineStatus);
        window.removeEventListener('offline', updateOnlineStatus);
    });

    return {
        isOnline
    };
}
