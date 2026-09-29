import { nextTick, onBeforeUnmount, onMounted } from 'vue';

/**
 * Scroll reveal. Elements matching `selector` get `.is-in` once they are on
 * screen; the CSS keeps their entrance animations paused until then (see
 * ".crm-*:not(.is-in)" in app.css). Call `observe()` again after the matching
 * elements change (e.g. a filtered list) to pick up the new ones.
 */
export function useReveal(selector) {
    let observer = null;

    function observe() {
        const targets = document.querySelectorAll(`${selector}:not(.is-in)`);

        if (typeof IntersectionObserver === 'undefined') {
            targets.forEach((el) => el.classList.add('is-in'));

            return;
        }

        observer ??= new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        );

        targets.forEach((el) => observer.observe(el));
    }

    onMounted(() => nextTick(observe));
    onBeforeUnmount(() => observer?.disconnect());

    return { observe: () => nextTick(observe) };
}
