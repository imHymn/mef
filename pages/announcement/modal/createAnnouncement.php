<!-- Create Announcement Modal (Bootstrap 4, vanilla JS) -->
<div class="modal fade" id="createAnnouncementModal" tabindex="-1" role="dialog" aria-labelledby="createAnnouncementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createAnnouncementLabel">
                    <!-- Optional Feather icon; render manually if needed -->
                    <span style="margin-right: 0.5rem;">📢</span> Create Announcement
                </h5>
                <button type="button" class="close text-white" id="closeCreateModalBtn" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="createAnnouncementForm">
                <div class="modal-body p-4">
                    <!-- Title -->
                    <div class="form-group mb-3">
                        <label for="title" class="font-weight-semibold">Title</label>
                        <input type="text" class="form-control" id="title" name="title" maxlength="70" placeholder="Enter announcement title" required>
                    </div>

                    <!-- Message -->
                    <div class="form-group mb-3">
                        <label for="message" class="font-weight-semibold">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" maxlength="255" placeholder="Write your announcement message..." required></textarea>
                    </div>

                    <!-- Category -->
                    <div class="form-group mb-3">
                        <label for="category" class="font-weight-semibold">Category</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="" disabled selected>Select category</option>
                            <option value="System Update">System Update</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Notice">Notice</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="form-group mb-3">
                        <label for="priority" class="font-weight-semibold">Priority</label>
                        <select class="form-control" id="priority" name="priority" required>
                            <option value="" disabled selected>Select priority</option>
                            <option value="Low">Low</option>
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group mb-3">
                        <label for="status" class="font-weight-semibold">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                            <option value="Draft">Draft</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="start_date" class="font-weight-semibold">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="end_date" class="font-weight-semibold">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" id="cancelCreateModalBtn">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    const form = document.getElementById("createAnnouncementForm");
    const categorySelect = document.getElementById("category");
    const statusSelect = document.getElementById("status");
    const createModal = document.getElementById("createAnnouncementModal");
    const closeBtn = document.getElementById("closeCreateModalBtn");
    const cancelBtn = document.getElementById("cancelCreateModalBtn");

    // Default status options
    const defaultStatuses = [{
            value: "Active",
            text: "Active"
        },
        {
            value: "Archived",
            text: "Archived"
        },
        {
            value: "Draft",
            text: "Draft"
        }
    ];

    // System Update specific statuses
    const systemUpdateStatuses = [{
            value: "Done",
            text: "Done"
        },
        {
            value: "Pending",
            text: "Pending"
        },
        {
            value: "Cancelled",
            text: "Cancelled"
        }
    ];

    // Update status options based on category
    function updateStatusOptions() {
        const selectedCategory = categorySelect.value;
        const options = (selectedCategory === "System Update" || selectedCategory === "Notice") ? systemUpdateStatuses : defaultStatuses;

        statusSelect.innerHTML = "";
        options.forEach(opt => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.textContent = opt.text;
            statusSelect.appendChild(option);
        });
    }

    // Watch category change
    categorySelect.addEventListener("change", updateStatusOptions);

    // ===== Modal Show/Hide Functions =====
    function showModal(modal) {
        modal.classList.add("show");
        modal.style.display = "block";
        modal.removeAttribute("aria-hidden");
        modal.setAttribute("aria-modal", "true");
        document.body.classList.add("modal-open");

        // Add backdrop
        const backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        document.body.appendChild(backdrop);
    }

    function hideModal(modal) {
        modal.classList.remove("show");
        modal.style.display = "none";
        modal.setAttribute("aria-hidden", "true");
        modal.removeAttribute("aria-modal");
        document.body.classList.remove("modal-open");

        // Remove backdrop
        const backdrop = document.querySelector(".modal-backdrop");
        if (backdrop) backdrop.remove();
    }

    // Close modal buttons
    closeBtn.addEventListener("click", () => hideModal(createModal));
    cancelBtn.addEventListener("click", () => hideModal(createModal));

    // Click outside modal to close
    createModal.addEventListener("click", (e) => {
        if (e.target === createModal) hideModal(createModal);
    });

    // ===== Form Submission =====
    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const data = {
            title: form.title.value.trim(),
            message: form.message.value.trim(),
            category: form.category.value,
            priority: form.priority.value,
            status: form.status.value,
            start_date: form.start_date.value,
            end_date: form.end_date.value
        };

        try {
            const response = await fetch("api/createAnnouncement", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) throw new Error("Network response was not ok");
            const result = await response.json();

            if (result.success) {
                showAlert("success", "Success", "Announcement created successfully!");
                form.reset();
                hideModal(createModal); // ✅ Native hide
                loadAnnouncements(); // reload table
            } else {
                showAlert("error", "Error", result.message);
            }
        } catch (error) {
            console.error("Error:", error);
            showAlert("error", "Error", "Failed to create announcement.");
        }
    });
</script>