if ("serviceWorker" in navigator) {
    window.addEventListener("load", async () => {
        try {
            const registration = await navigator.serviceWorker.register("/sw.js", {
                updateViaCache: "none", // never use the HTTP cache for sw.js
            });
            console.log("Service Worker registered with scope:", registration.scope);

            // Check for a new sw.js now, and whenever the tab becomes visible again
            registration.update();
            document.addEventListener("visibilitychange", () => {
                if (document.visibilityState === "visible") registration.update();
            });
        } catch (error) {
            console.log("Service Worker registration failed:", error);
        }
    });

    // When a new worker takes control (via skipWaiting + clients.claim),
    // reload once so the page uses the fresh content
    let reloading = false;
    navigator.serviceWorker.addEventListener("controllerchange", () => {
        if (reloading) return;
        reloading = true;
        window.location.reload();
    });
}