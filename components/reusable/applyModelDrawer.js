document.addEventListener("DOMContentLoaded", function() {
    const savedModel = localStorage.getItem("selectedModel");

    getData(savedModel);

    const displayEl = document.getElementById("selectedModelDisplay");

    const myDrawer = new ModelDrawer({
        initialModel: savedModel || "L300 DIRECT",
        onModelChange: (model) => {
            console.log("Model selected:", model);
            localStorage.setItem("selectedModel", model);

            // ✅ Update header display
            if (displayEl) {
                const viewportText = displayEl.dataset.viewport || "";
                displayEl.textContent = model + " " + viewportText;
            }

            getData(model);
        },
    });

    // ✅ Show saved model on initial load
    if (savedModel && displayEl) {
        displayEl.textContent = savedModel;
    }

    // 🌐 Update viewport size
    function updateViewport() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const viewportText = `(${width}×${height})`;
        console.log(viewportText)
        if (displayEl) {
            // Save viewport in data attribute to preserve model text
            displayEl.dataset.viewport = viewportText;

            // Update text
            const currentModel = localStorage.getItem("selectedModel") || displayEl.textContent.split(" ")[0];
            displayEl.textContent = `${currentModel} ${viewportText}`;
        }
    }

    // Initial viewport
    updateViewport();

    // Update on resize
    window.addEventListener("resize", updateViewport);
});
