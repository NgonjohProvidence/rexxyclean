(() => {
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let isNavigating = false;

    const clearTransition = () => {
        document.querySelectorAll(".page-transition").forEach(transition => transition.remove());
        isNavigating = false;
    };

    window.addEventListener("pageshow", clearTransition);
    window.addEventListener("pagehide", clearTransition);

    document.addEventListener("click", event => {
        if (!(event.target instanceof Element) || event.defaultPrevented || reducedMotion.matches) {
            return;
        }

        const link = event.target.closest("a[href]");
        if (!link || link.hasAttribute("download") || (link.target && link.target !== "_self")) {
            return;
        }

        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const rawHref = link.getAttribute("href");
        if (!rawHref || rawHref.startsWith("#")) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin || destination.href === window.location.href) {
            return;
        }

        event.preventDefault();
        if (isNavigating) {
            return;
        }
        isNavigating = true;
        window.setTimeout(() => {
            isNavigating = false;
        }, 1500);

        const transition = document.createElement("div");
        transition.className = "page-transition";
        transition.setAttribute("aria-hidden", "true");
        document.body.append(transition);
        window.setTimeout(() => window.location.assign(destination.href), 150);
    });
})();