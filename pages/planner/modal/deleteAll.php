<style>
    /* Override Bootstrap's default modal backdrop */
    .custom-backdrop {
        background-color: rgba(0, 0, 0, 0.6);
        /* dark semi-transparent black */
        backdrop-filter: blur(4px);
        /* optional blur effect */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1040;
        /* below modal (1050) */
    }

    /* Ensure modal always appears above the custom backdrop */
    #filterDeleteModal {
        z-index: 1050;
    }
</style>
<div class="modal fade" id="filterDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Select Filter for Deletion</h5>
                <button type="button" class="btn-close" onclick="closeDeleteModal()"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="filter-column" class="form-label">Filter Column</label>
                    <select id="filter-column" class="form-select">
                        <option value="lot_no">Lot Number</option>
                        <option value="date_needed">Date Needed</option>
                        <option value="created_at">Date Filed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="filter-value" class="form-label">Value</label>
                    <select id="filter-value" class="form-select">
                        <option disabled selected>Select value</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="handleDeleteFilter()">Delete</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openDeleteModal() {
        const modal = document.getElementById('filterDeleteModal');
        modal.classList.add('show');
        modal.style.display = 'block';

        const backdrop = document.createElement('div');
        backdrop.className = 'custom-backdrop';
        backdrop.id = 'custom-modal-backdrop';
        document.body.appendChild(backdrop);

        document.body.classList.add('modal-open');

        // Trigger population
        document.getElementById('filter-column').dispatchEvent(new Event('change'));
    }


    function closeDeleteModal() {
        // Hide modal
        const modal = document.getElementById('filterDeleteModal');
        modal.classList.remove('show');
        modal.style.display = 'none';

        // Remove custom backdrop
        const backdrop = document.getElementById('custom-modal-backdrop');
        if (backdrop) backdrop.remove();

        document.body.classList.remove('modal-open');
    }


    document.getElementById('filter-column').addEventListener('change', function() {
        const column = this.value;
        const valueSelect = document.getElementById('filter-value');
        valueSelect.innerHTML = '<option disabled selected>Select value</option>';

        let values = [];

        if (column === 'lot_no') {
            values = [...new Set(filteredData.map(item => String(item.lot_no)))];

        } else if (column === 'date_needed') {
            values = [...new Set(filteredData.map(item => item.date_needed))];
        } else if (column === 'created_at') {
            values = [...new Set(filteredData.map(item => item.created_at))];
        }

        values.forEach(val => {
            const option = document.createElement('option');
            option.value = val;
            option.textContent = val;
            valueSelect.appendChild(option);
        });
    });

    function handleDeleteFilter() {
        const column = document.getElementById('filter-column').value;
        const value = document.getElementById('filter-value').value.trim();
        console.log(column, value)
        if (!value) {
            showAlert('warning', 'Input required', 'Please enter a value to filter.');
            return;
        }



        // Filter current data based on selected field
        const matches = originalData.filter(item => {
            const field = item[column];
            return field && field.toString().toLowerCase().includes(value.toLowerCase());
        });

        if (matches.length === 0) {
            showAlert('info', 'No matches', 'No entries found for that filter.');
            return;
        }


        closeDeleteModal(); // Hide modal

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${matches.length} record(s) filtered by "${column}: ${value}"`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'swal-sm' // add your custom CSS class
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const idsToDelete = matches.map(item => item.id);
                console.log(idsToDelete)
                fetch('api/planner/deleteMultipleForm', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            column: column,
                            value: value
                        })

                    })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            showAlert('success', 'Deleted!', 'Filtered entries deleted.');

                            // Update your filtered data
                            filteredData = filteredData.filter(item => !idsToDelete.includes(item.id));
                            paginator.setData(filteredData);

                        } else {
                            showAlert('error', 'Error!', response.message || 'Delete failed.');
                        }

                    });
            }
        });
    }
</script>