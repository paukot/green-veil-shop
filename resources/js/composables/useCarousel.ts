import {
    ref,
    watch,
    onMounted,
    onUnmounted,
    type Ref,
    type ComputedRef,
} from 'vue';

export interface UseCarouselOptions {
    itemCount: Ref<number> | ComputedRef<number>;
    intervalMs?: number;
    autoPlay?: boolean;
}

/**
 * Reusable carousel slide index, circular navigation, and autoplay management.
 */
export function useCarousel(options: UseCarouselOptions) {
    const { itemCount, intervalMs = 5000, autoPlay = true } = options;

    const currentIndex = ref(0);
    const isPaused = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;

    function stopTimer() {
        if (!timer) return;
        clearInterval(timer);
        timer = null;
    }

    function startTimer() {
        stopTimer();
        if (!autoPlay || itemCount.value <= 1) return;

        timer = setInterval(() => {
            if (isPaused.value) return;
            next();
        }, intervalMs);
    }

    function next() {
        if (itemCount.value <= 1) return;
        currentIndex.value = (currentIndex.value + 1) % itemCount.value;
    }

    function prev() {
        if (itemCount.value <= 1) return;
        currentIndex.value =
            (currentIndex.value - 1 + itemCount.value) % itemCount.value;
    }

    function goTo(index: number) {
        if (index < 0 || index >= itemCount.value) return;
        currentIndex.value = index;
        startTimer();
    }

    function pause() {
        isPaused.value = true;
    }

    function resume() {
        isPaused.value = false;
    }

    watch(
        () => itemCount.value,
        (count) => {
            if (currentIndex.value >= count) {
                currentIndex.value = 0;
            }
            if (count > 1 && autoPlay) {
                startTimer();
            } else {
                stopTimer();
            }
        },
    );

    onMounted(() => {
        if (autoPlay && itemCount.value > 1) {
            startTimer();
        }
    });

    onUnmounted(() => {
        stopTimer();
    });

    return {
        currentIndex,
        isPaused,
        next,
        prev,
        goTo,
        pause,
        resume,
        startTimer,
        stopTimer,
    };
}
