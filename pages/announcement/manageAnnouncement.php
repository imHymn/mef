<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include 'modal/createAnnouncement.php'; ?>
<?php include 'modal/editAnnouncement.php'; ?>

<style>
    table.custom-hover th,
    table.custom-hover td {
        white-space: nowrap;
        /* keep in one line */
        overflow: hidden;
        /* hide overflow text */
        text-overflow: ellipsis;
        /* show … for overflow */
        max-width: 250px;
        /* prevent long content from expanding table */
    }

    .table-responsive {
        overflow-x: auto;
    }
</style>
<div class="page-content">
    <nav class="page-breadcrumb d-flex justify-content-between align-items-center">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Announcement</li>
        </ol>
    </nav>
    <div class="row ">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center ">
                        <h6 class="card-title mb-0">Announcement List</h6>
                        <div>
                            <button id="openCreateModalBtn" class="btn btn-primary mb-3">
                                Create Announcement
                            </button>

                        </div>
                    </div>

                    <div class="row mb-3 col-md-3">

                        <input
                            type="text"
                            id="filter-input"
                            class="form-control"
                            placeholder="Type to filter..." />

                    </div>

                    <table class="custom-hover table">
                        <thead>
                            <tr style="text-align:center;">

                                <th style="width: 15%;">Title <span class="sort-icon"></span></th>
                                <th style="width: 15%;">Message <span class="sort-icon"></span></th>
                                <th style="width: 10%;">Category <span class="sort-icon"></span></th>
                                <th style="width: 10%;">Priority <span class="sort-icon"></span></th>
                                <th style="width: 10%;">Status <span class="sort-icon"></span></th>
                                <th style="width: 10%;">Created At <span class="sort-icon"></span></th>
                                <th style="width: 5%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="announcement-body"></tbody>
                    </table>

                    <div id="pagination-controls" class="mt-2 text-center"></div>

                </div>
            </div>
        </div>
    </div>

    </form>
</div>
</div>
</div>
<script>
    const openCreateModalBtn = document.getElementById("openCreateModalBtn");
    openCreateModalBtn.addEventListener("click", () => showModal(createModal));


    // Load Announcements into table
    function loadAnnouncements() {
        const tbody = document.getElementById("announcement-body");
        tbody.innerHTML = `<tr><td colspan="9" class="text-center">Loading...</td></tr>`;

        fetch("api/getAnnouncement")
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = data.map(item => `
                    <tr>
                        <td>${item.title || '-'}</td>
                        <td>${item.message || '-'}</td>
                        <td>${item.category || '-'}</td>
                        <td>${item.priority || '-'}</td>
                        <td>${item.status || '-'}</td>
                        <td>${item.created_at || '-'}</td>
                        <td>
                          <button 
                            class="btn btn-sm btn-primary btn-edit" 
                            data-id="${item.id}"
                            data-title="${item.title}"
                            data-message="${item.message}"
                            data-category="${item.category}"
                            data-priority="${item.priority}"
                            data-status="${item.status}"
                            data-start-date="${item.start_date || ''}"
                            data-end-date="${item.end_date || ''}">
                            Edit
                        </button>

                            <button class="btn btn-sm btn-danger btn-delete" data-id="${item.id}">Delete</button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(err => {
                console.error("Error loading announcements:", err);
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Failed to load announcements.</td></tr>`;
            });
    }

    // Safely handle clicks on edit/delete buttons
    document.body.addEventListener("click", async (e) => {
        try {
            const editBtn = e.target.closest(".btn-edit");
            const deleteBtn = e.target.closest(".btn-delete");

            // 🟡 EDIT BUTTON
            if (editBtn) {
                const announcement = {
                    id: editBtn.dataset.id,
                    title: editBtn.dataset.title,
                    message: editBtn.dataset.message,
                    category: editBtn.dataset.category,
                    priority: editBtn.dataset.priority,
                    status: editBtn.dataset.status,
                    start_date: editBtn.dataset.startDate,
                    end_date: editBtn.dataset.endDate
                };

                openEditAnnouncementModal(announcement);
                return;
            }

            // 🔴 DELETE BUTTON
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                if (!id) return;

                const confirm = await Swal.fire({
                    title: "Are you sure?",
                    text: "This will permanently delete the announcement.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it",
                    cancelButtonText: "Cancel"
                });

                if (!confirm.isConfirmed) return;

                const response = await fetch("api/deleteAnnouncement", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert("success", "Deleted!", result.message);
                    loadAnnouncements(); // refresh table
                } else {
                    showAlert("error", "Error", result.message);
                }
            }
        } catch (err) {
            console.error("Error in click handler:", err);
        }
    });

    // Initial load
    loadAnnouncements();
</script>