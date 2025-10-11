<script src="/mes/assets/js/sweetalert2@11.js"></script>


<style>
    /* Background overlay */
    .announcement-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        /* hidden by default */
        justify-content: center;
        align-items: center;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1055;
    }

    /* Popup box */
    .announcement-content {
        background: #fff;
        padding: 2rem;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: fadeIn 0.3s ease-in-out;
    }

    .announcement-content h3 {
        font-size: 1.4rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .announcement-content p {
        font-size: 1rem;
        color: #333;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .announcement-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .announcement-footer label {
        font-size: 0.9rem;
        color: #555;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>
<!-- Announcements Modal -->
<div class="modal fade" id="announcementsModal" tabindex="-1" role="dialog" aria-labelledby="announcementsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="announcementsModalLabel">
                    </i> Announcements
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" id="closeAnnouncementsModal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-2">
                <ul class="nav nav-tabs" id="announcementsTab" role="tablist"></ul>
                <div class="tab-content p-1" id="announcementsTabContent" style="border: 1px solid #ccc; border-radius: 5px;"></div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <label class="mr-auto mb-0">
                    <input type="checkbox" id="dontShowAnnouncementsToday" class="mr-2"> Don’t show again today
                </label>
                <button type="button" class="btn btn-secondary" id="closeAnnouncementsFooter">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        let groupedAnnouncements = null; // Cache

        async function loadAnnouncements() {
            try {
                const response = await fetch("api/getAnnouncement");
                if (!response.ok) throw new Error("Network error");

                const data = await response.json();
                if (!Array.isArray(data) || data.length === 0) return;

                const today = new Date().toISOString().split("T")[0];

                const filteredData = data.filter(item => {
                    const start = item.start_date || today;
                    const end = item.end_date || today;
                    return today <= end;
                });

                groupedAnnouncements = filteredData.reduce((acc, item) => {
                    const cat = item.category || "General";
                    if (!acc[cat]) acc[cat] = [];
                    acc[cat].push(item);
                    return acc;
                }, {});

                showAnnouncementsModal(groupedAnnouncements);

            } catch (err) {
                console.error("Error loading announcements:", err);
            }
        }

        function showAnnouncementsModal(grouped, force = false) {
            if (!grouped) return;

            const hideKey = `hideAnnouncements_${new Date().toISOString().split("T")[0]}`;
            if (!force && localStorage.getItem(hideKey)) return;

            const tabNav = document.getElementById("announcementsTab");
            const tabContent = document.getElementById("announcementsTabContent");
            tabNav.innerHTML = "";
            tabContent.innerHTML = "";

            const categories = Object.keys(grouped);

            // 🔹 Force System Update first
            categories.sort((a, b) => {
                if (a === "System Update") return -1;
                if (b === "System Update") return 1;
                return 0;
            });

            categories.forEach((category, index) => {
                // ---- Tab Button ----
                const li = document.createElement("li");
                li.className = "nav-item me-2";
                const btn = document.createElement("a");
                btn.className = "nav-link px-3 py-2 " + (index === 0 ? "active bg-primary text-white rounded" : "bg-light text-dark rounded");
                btn.href = "#";
                btn.textContent = category;
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    setActiveTab(index);
                });
                li.appendChild(btn);
                tabNav.appendChild(li);

                // ---- Tab Pane ----
                const tabPane = document.createElement("div");
                tabPane.className = "tab-pane " + (index === 0 ? "active" : "");
                tabPane.style.display = index === 0 ? "block" : "none";
                tabPane.style.maxHeight = "400px"; // scrollable if too many items
                tabPane.style.overflowY = "auto";
                tabPane.style.padding = "10px";

                grouped[category].forEach(item => {
                    const itemDiv = document.createElement("div");
                    itemDiv.className = "border rounded p-3 mb-1 bg-white shadow-sm announcement-item";
                    itemDiv.style.transition = "transform 0.2s ease";

                    itemDiv.addEventListener("mouseover", () => itemDiv.style.transform = "scale(1.02)");
                    itemDiv.addEventListener("mouseout", () => itemDiv.style.transform = "scale(1)");

                    // Title
                    const title = document.createElement("h5");
                    title.textContent = item.title || "No title";
                    title.className = "fw-bold mb-2 d-flex justify-content-between align-items-center";

                    // Status badge
                    const badge = document.createElement("span");
                    badge.className = "badge";
                    badge.style.marginLeft = "10px";

                    if (item.status === "Cancelled") {
                        badge.classList.add("bg-danger"); // red badge
                        badge.textContent = "Cancelled";
                    }
                    title.appendChild(badge);
                    itemDiv.appendChild(title);

                    // Message
                    if (item.message) {
                        const msg = document.createElement("p");
                        msg.style.margin = "0";
                        item.message.split("\n").forEach(line => {
                            const lineDiv = document.createElement("div");
                            lineDiv.textContent = line;
                            msg.appendChild(lineDiv);
                        });
                        itemDiv.appendChild(msg);
                    }

                    // Show cancel reason if status is Cancelled
                    if (item.status === "Cancelled" && item.reason) {
                        const reasonDiv = document.createElement("div");
                        reasonDiv.className = "mt-2";
                        reasonDiv.innerHTML = `<strong>Reason:</strong> ${item.reason}`;
                        itemDiv.appendChild(reasonDiv);
                    }

                    tabPane.appendChild(itemDiv);
                });


                tabContent.appendChild(tabPane);
            });

            function setActiveTab(activeIndex) {
                tabContent.querySelectorAll(".tab-pane").forEach((pane, i) => {
                    pane.style.display = i === activeIndex ? "block" : "none";
                });
                tabNav.querySelectorAll(".nav-link").forEach((tab, i) => {
                    if (i === activeIndex) {
                        tab.classList.add("active", "bg-primary", "text-white");
                        tab.classList.remove("bg-light", "text-dark");
                    } else {
                        tab.classList.remove("active", "bg-primary", "text-white");
                        tab.classList.add("bg-light", "text-dark");
                    }
                });
            }

            const modal = document.getElementById("announcementsModal");
            modal.classList.add("show");
            modal.style.display = "block";
            modal.style.opacity = 0;
            document.body.classList.add("modal-open");

            const backdrop = document.createElement("div");
            backdrop.className = "modal-backdrop fade show";
            document.body.appendChild(backdrop);

            setTimeout(() => modal.style.opacity = 1, 50); // smooth fade-in

            const closeModal = () => {
                modal.style.opacity = 0;
                setTimeout(() => {
                    modal.classList.remove("show");
                    modal.style.display = "none";
                    document.body.classList.remove("modal-open");
                    document.body.querySelectorAll(".modal-backdrop").forEach(b => b.remove());
                }, 200);
            };

            document.getElementById("closeAnnouncementsModal").addEventListener("click", closeModal);
            document.getElementById("closeAnnouncementsFooter").addEventListener("click", closeModal);
            modal.addEventListener("click", (e) => {
                if (e.target === modal) closeModal();
            });

            // Don't show again today
            const checkbox = document.getElementById("dontShowAnnouncementsToday");
            checkbox.addEventListener("change", function() {
                if (this.checked) localStorage.setItem(hideKey, "true");
            });
        }

        loadAnnouncements();

        // Manual trigger (profile icon)
        const announcementIcon = document.getElementById("announcementTrigger");
        if (announcementIcon) {
            announcementIcon.addEventListener("click", () => {
                if (groupedAnnouncements) showAnnouncementsModal(groupedAnnouncements, true);
            });
        }
    });
</script>