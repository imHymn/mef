<script src="assets/js/sweetalert2@11.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.min.js"></script>
<link rel="stylesheet" href="assets/css/choices.min.css" />
<script src="assets/js/choices.min.js"></script>
<style>
    .custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
        /* light blue */
    }
</style>
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Planner Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">CUSTOMER Form</h6>
                    <form id="delivery_form">
                        <div class="form-row">
                            <div class="form-group col-md-3 col-lg-2">
                                <label for="customer_name">Customer Name</label>
                                <select class="form-control" id="customerSelect" name="customer_name" required></select>
                            </div>

                            <div class="form-group col-md-3 col-lg-2">
                                <label for="date_needed">Due Date</label>
                                <div class="input-group">
                                    <input type='date' class='form-control form-control-sm' id='date_needed' name='date_needed' style='width: 160px;'>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div id="title">
                            <h6 class="card-title">COMPONENTS LIST</h6>
                        </div>
                        <div id="material_components"></div>
                        <button id="delivery_submit_btn" type="button" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Load dropdown options
        fetch('api/reusable/getCustomerandModel')
            .then(res => res.json())
            .then(({
                data
            }) => {
                if (!Array.isArray(data)) throw new Error('Invalid API data');
                const filtered = data.filter(item => item.status === "customer");

                // 🔹 extract unique customers and models from filtered list
                const customers = [...new Set(filtered.map(item => item.customer_name))];
                const models = [...new Set(filtered.map(item => item.model).filter(name => name))];


                populateDropdown('customerSelect', customers);
                populateDropdown('modelSelect', models);
            })
            .catch(err => {
                console.error('Error fetching customer/model data:', err);
                showAlert('error', 'Error', 'Failed to load customer and model data.');


            });

        function populateDropdown(selectId, items) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.error(`Element with ID "${selectId}" not found.`);
                return;
            }
            select.innerHTML = '<option disabled selected>Select</option>';
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item;
                option.textContent = item;
                select.appendChild(option);
            });
        }

        document.getElementById('customerSelect')?.addEventListener('change', handleDropdownChange);
        document.getElementById('modelSelect')?.addEventListener('change', handleDropdownChange);

        function handleDropdownChange() {
            const customerSelect = document.getElementById('customerSelect');
            const container = document.getElementById('material_components');

            if (!customerSelect || !container) {
                console.error('Required HTML elements not found.');
                return;
            }

            const customer = customerSelect.value;
            if (!customer || customer === 'Select') {
                container.innerHTML = '';
                return;
            }

            // Decide model name only from customer
            let modelName = (customer === 'VALERIE' || customer === 'PNR') ? 'L300 DIRECT' : customer;

            const params = new URLSearchParams({
                customer_name: customer,
                model: modelName
            });

            fetch(`api/planner/getComponents?${params.toString()}`)
                .then(res => res.json())
                .then(components => {
                    console.log(components)
                    container.innerHTML = '';

                    if (!Array.isArray(components) || components.length === 0) {
                        container.innerHTML = `<div class="alert alert-warning text-center">No components found for the selected model and customer.</div>`;
                        return;
                    }

                    const table = document.createElement('table');
                    table.className = 'custom-hover table table-bordered table-hover ';
                    table.innerHTML = `
     
            <thead>
                <tr>
                    <th>Material No.</th>
                    <th>Component Name</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                ${components.map((row, index) => `
                    <tr>
                        <td class="materialNo">${row.material_no}</td>
                        <td class="componentsName" style="white-space: normal; word-wrap: break-word;">${row.components_name}</td>
                        <td class="process" style="display: none;">${row.process || ''}</td>
                        <td class="text-center">
                            <input type="number" class="form-control perRowQty" data-index="${index}" min="0" step="1" value="0" />
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm removeRow">
                                 Remove
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        `;

                    container.appendChild(table);

                    // Add event listener for remove buttons
                    container.querySelectorAll('.removeRow').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const row = this.closest('tr');
                            row.remove();
                        });
                    });
                })
                .catch(err => {
                    console.error('Error fetching components:', err);
                    showAlert('error', 'Error', 'Failed to fetch component data.');

                });

        }

        document.getElementById('delivery_submit_btn')?.addEventListener('click', () => {
            const dateNeeded = document.getElementById('date_needed')?.value || '';
            const customer = document.getElementById('customerSelect')?.value || '';

            // Decide model name only from customer
            let modelName = customer;

            const rows = document.querySelectorAll('#material_components table tbody tr');

            if (!dateNeeded) {
                showAlert('error', 'Missing Date Needed', 'Please choose a “Date Needed” before submitting.');
                return;
            }


            const payload = [];
            rows.forEach(row => {
                const material_no = row.querySelector('.materialNo')?.innerText.trim() || '';
                const components_name = row.querySelector('.componentsName')?.innerText.trim() || '';
                const process = row.querySelector('.process')?.innerText.trim() || '';
                const qtyInput = row.querySelector('.perRowQty');
                const quantity = parseInt(qtyInput?.value) || 0;

                if (!material_no || !components_name || quantity <= 0) return;

                payload.push({
                    material_no,
                    components_name,
                    customer_name: customer,
                    model: modelName,
                    quantity,
                    total_quantity: quantity,
                    status: 'pending',
                    section: 'DELIVERY',
                    date_needed: dateNeeded,
                    process
                });
            });

            if (payload.length === 0) {
                showAlert('error', 'No Data', 'No valid components with quantity found to submit.');
                return;
            }

            console.log('Submitting payload:', payload);

            Swal.fire({
                title: 'Confirm Submission',
                text: `You are about to submit ${payload.length} item(s). Proceed?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                    popup: 'swal-sm'
                },
                buttonsStyling: false
            }).then(result => {
                if (result.isConfirmed) {
                    // 🔹 Decide API URL based on model
                    const modelName = payload[0]?.model || "";
                    let apiUrl = 'api/planner/submitForm_allCustomer';

                    if (['MILLIARD', 'APS', 'KOMYO'].includes(modelName.toUpperCase())) {
                        apiUrl = 'api/planner/submitForm_specificCustomer';
                    }

                    fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.status === 'error') {
                                const issues = response.insufficient_items?.map(item => `
                    <li><strong>${item.material_no}</strong>: ${item.components_name}<br><small>${item.reason}</small></li>
                `).join('');

                                showAlert(
                                    'error',
                                    'Insufficient Stock',
                                    `${response.message}\n${issues}`
                                );

                                return;
                            }
                            showAlert('success', 'Success', 'Your delivery request was submitted.');

                        })
                        .catch(err => {
                            console.error('Submission error:', err);
                            showAlert('error', 'Network Error', 'Failed to submit data. Please try again.');

                        });
                }
            });

        });
    });
</script>