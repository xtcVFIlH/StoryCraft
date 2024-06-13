const startKey = `__longPressStart_${Math.random().toString(36).substr(2, 9)}`;
const cancelKey = `__longPressCancel_${Math.random().toString(36).substr(2, 9)}`;

const longPress = {
    mounted(el, binding) {
        if (typeof binding.value !== 'function') {
            throw 'callback must be a function';
        }
        let pressTimer = null;

        el.style.userSelect = 'none';

        const start = (e) => {
            if (e.type === 'click' && e.button !== 0) {
                return;
            }
            if (pressTimer === null) {
                pressTimer = setTimeout(() => {
                    binding.value(e);
                }, 500);
            }
        };

        const cancel = () => {
            if (pressTimer !== null) {
                clearTimeout(pressTimer);
                pressTimer = null;
            }
        };

        el[startKey] = start;
        el[cancelKey] = cancel;
    
        el.addEventListener("touchstart", start);
        el.addEventListener("touchend", cancel);
        el.addEventListener("touchcancel", cancel);
    },
    unmounted(el) {
        const start = el[startKey];
        const cancel = el[cancelKey];

        el.style.userSelect = '';

        el.removeEventListener("touchstart", start);
        el.removeEventListener("touchend", cancel);
        el.removeEventListener("touchcancel", cancel);
    }
};

export default longPress;