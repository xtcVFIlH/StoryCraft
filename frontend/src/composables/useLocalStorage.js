import { ref, onMounted, onUnmounted } from 'vue';

const refsCache = {};

export function useLocalStorage(key, defaultValue = null) {
    if (!refsCache[key]) {
        const storedValue = localStorage.getItem(key);
        const value = ref(storedValue !== null ? JSON.parse(storedValue) : defaultValue);
        let intervalId = null;

        onMounted(() => {
            intervalId = setInterval(() => {
                localStorage.setItem(key, JSON.stringify(value.value));
            }, 1000);
        });

        onUnmounted(() => {
            clearInterval(intervalId);
        });

        refsCache[key] = value;
    }

    return refsCache[key];
}
