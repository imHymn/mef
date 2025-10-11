<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editAnnouncementLabel">
                    <i data-feather="edit" class="mr-2"></i> Edit Announcement
                </h5>
                <button type="button" class="close text-white" id="editModalCloseX" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>



            </div>

            <div class="modal-body p-4">
                <input type="hidden" id="announcement_id" name="announcement_id" />

                <div class="form-group mb-3">
                    <label for="edit_title">Title</label>
                    <input type="text" class="form-control" id="edit_title" name="title" maxlength="70" required />
                </div>

                <div class="form-group mb-3">
                    <label for="edit_message">Message</label>
                    <textarea class="form-control" id="edit_message" name="message" rows="3" required></textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="edit_category">Category</label>
                    <select class="form-control" id="edit_category" name="category" required>
                        <option value="System Update">System Update</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Notice">Notice</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="edit_priority">Priority</label>
                    <select class="form-control" id="edit_priority" name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Normal">Normal</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="edit_status">Status</label>
                    <select class="form-control" id="edit_status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Archived">Archived</option>
                        <option value="Draft">Draft</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="edit_start_date">Start Date</label>
                    <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                </div>

                <!-- End Date -->
                <div class="form-group mb-3">
                    <label for="edit_end_date">End Date</label>
                    <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                </div>
                <div class="form-group mb-3 d-none" id="cancelReasonWrapper">
                    <label for="cancel_reason">Reason for Cancellation</label>
                    <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="2" placeholder="Enter reason for cancellation..."></textarea>
                </div>

            </div>
            <!-- Start Date -->


            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" id="editModalCancel" aria-label="Close">
                    Cancel
                </button>
                <button type="button" class="btn btn-warning text-white" id="btnUpdateAnnouncement" onclick="updateAnnouncement()">
                    Update Announcement
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // X button
    document.getElementById("editModalCloseX").addEventListener("click", closeEditModal);

    // Cancel button
    document.getElementById("editModalCancel").addEventListener("click", closeEditModal);

    // Reusable close function
    function closeEditModal() {
        const reasonWrapper = document.getElementById("cancelReasonWrapper");
        reasonWrapper.classList.remove("d-none");
        const modalElement = document.getElementById("editAnnouncementModal");
        modalElement.classList.remove("show");
        modalElement.style.display = "none";
        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("overflow");
        document.body.style.removeProperty("padding-right");
        document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());

        // Optional: clear fields
        ["announcement_id", "edit_title", "edit_message", "edit_category", "edit_priority", "edit_status", "edit_start_date", "edit_end_date", "cancel_reason"].forEach(id => {
            document.getElementById(id).value = '';
        });

        // Hide cancel reason wrapper
        document.getElementById("cancelReasonWrapper").classList.add("d-none");
    }


    function updateStatusOptions(category, currentStatus = null) {
        const statusSelect = document.getElementById("edit_status");
        const cancelReasonWrapper = document.getElementById("cancelReasonWrapper");

        statusSelect.addEventListener("change", () => {
            if (statusSelect.value === "Cancelled") {
                cancelReasonWrapper.classList.remove("d-none");
            } else {
                cancelReasonWrapper.classList.add("d-none");
                document.getElementById("cancel_reason").value = '';
            }
        });

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
            },
        ];

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
            },
        ];

        const options = (category === "System Update" || category === "Notice") ? systemUpdateStatuses : defaultStatuses;

        statusSelect.innerHTML = "";
        options.forEach((opt) => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.value === currentStatus) option.selected = true;
            statusSelect.appendChild(option);
        });
    }

    document.addEventListener("DOMContentLoaded", function() {

        const categorySelect = document.getElementById("edit_category");
        categorySelect.addEventListener("change", () => {
            updateStatusOptions(categorySelect.value);
        });
    });

    function openEditAnnouncementModal(announcement) {
        try {
            // Fill the fields
            document.getElementById("announcement_id").value = announcement.id;
            document.getElementById("edit_title").value = announcement.title;
            document.getElementById("edit_message").value = announcement.message;
            document.getElementById("edit_category").value = announcement.category;
            document.getElementById("edit_priority").value = announcement.priority;
            updateStatusOptions(announcement.category, announcement.status);

            document.getElementById("edit_start_date").value = announcement.start_date || '';
            document.getElementById("edit_end_date").value = announcement.end_date || '';

            // Bootstrap 4 manual show
            const modalElement = document.getElementById('editAnnouncementModal');
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');

            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        } catch (err) {
            console.error("Error opening edit modal:", err);
        }
    }
    async function updateAnnouncement() {
        const modalElement = document.getElementById("editAnnouncementModal");

        const data = {
            id: document.getElementById("announcement_id").value,
            title: document.getElementById("edit_title").value.trim(),
            message: document.getElementById("edit_message").value.trim(),
            category: document.getElementById("edit_category").value,
            priority: document.getElementById("edit_priority").value,
            status: document.getElementById("edit_status").value,
            start_date: document.getElementById("edit_start_date").value,
            end_date: document.getElementById("edit_end_date").value
        };
        if (data.status === "Cancelled") {
            const cancelReason = document.getElementById("cancel_reason").value.trim();
            if (!cancelReason) {
                showAlert("warning", "Required", "Please provide a reason for cancellation.");
                return; // Stop updating
            }
            data.cancel_reason = cancelReason; // include reason in the request
        }
        // Optional validation
        if (new Date(data.end_date) < new Date(data.start_date)) {
            showAlert("error", "Error", "End date cannot be before start date.");
            return;
        }

        try {
            const response = await fetch("api/updateAnnouncement", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            // Close modal manually
            modalElement.classList.remove("show");
            modalElement.style.display = "none";
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("overflow");
            document.body.style.removeProperty("padding-right");
            document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());

            // Clear fields manually
            ["announcement_id", "edit_title", "edit_message", "edit_category", "edit_priority", "edit_status", "edit_start_date", "edit_end_date"].forEach(id => {
                document.getElementById(id).value = '';
            });

            if (result.success) {
                showAlert("success", "Updated", "Announcement updated successfully!");
            } else {
                showAlert("error", "Error", "Failed to update announcement.");
            }

            loadAnnouncements(); // Reload table
        } catch (err) {
            console.error("Update failed:", err);
            showAlert("error", "Error", "Failed to update announcement.");
        }
    }
</script>