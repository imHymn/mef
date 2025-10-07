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
          <div class="col-12 grid-margin stretch-card">
              <div class="card">
                  <div class="card-body">
                      <h6 class="card-title">MUFFLER Form</h6>
                      <form id="delivery_form">
                          <div class="row g-3">
                              <!-- Customer Name -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="customer_name" class="form-label">Customer Name</label>
                                  <select class="form-control form-control-sm" id="customerSelect" name="customer_name" required>
                                  </select>
                              </div>

                              <!-- Model Name -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="model" class="form-label">Model Name</label>
                                  <select class="form-control form-control-sm" id="modelSelect" name="model" required>
                                  </select>
                              </div>

                              <!-- Shift Schedule -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="shifting" class="form-label">Shift Schedule</label>
                                  <select class="form-control form-control-sm" id="shifting" name="shifting" required>
                                      <option value="" disabled selected>Choose Shift:</option>
                                      <option value="1st Shift">1st Shift</option>
                                      <option value="2nd Shift">2nd Shift</option>
                                  </select>
                              </div>

                              <!-- Due Date -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="date_needed" class="form-label">Due Date</label>
                                  <input type='date' class='form-control form-control-sm' id='date_needed' name='date_needed'>
                              </div>
                          </div>

                          <hr>

                          <div id="material_components">
                              <div id="title">
                                  <h6 class="card-title">COMPONENTS LIST</h6>
                              </div>
                          </div>

                          <button id="delivery_submit_btn" type="button" class="btn btn-primary mt-3">Submit Request</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>



  <script>
      document.addEventListener('DOMContentLoaded', function() {
          let data = [];

          fetch('api/reusable/getCustomerandModel')
              .then(response => response.json())
              .then(result => {
                  data = result.data;

                  data = result.data.filter(item => item.status === "1");

                  if (!Array.isArray(data)) {
                      throw new Error('Invalid data format from API');
                  }

                  // Populate customers initially
                  const customers = [...new Set(
                      data
                      .map(item => item.customer_name)
                      .filter(name => name !== 'VALERIE' && name !== 'PNR')
                  )];

                  populateDropdown('customerSelect', customers);
                  populateDropdown('modelSelect', []); // Empty until customer is chosen
              })
              .catch(error => {
                  console.error('Error fetching customer/model data:', error);
                  Swal.fire({
                      title: 'Error',
                      text: 'Failed to load customer and model data.',
                      icon: 'error',
                      customClass: {
                          popup: 'swal-sm'
                      }
                  });

              });

          function populateDropdown(selectId, items) {
              const select = document.getElementById(selectId);
              select.innerHTML = '<option value="" disabled selected>Select</option>';
              items.forEach(item => {
                  const option = document.createElement('option');
                  option.value = item;
                  option.textContent = item;
                  select.appendChild(option);
              });
          }

          // When customer changes → filter models
          document.getElementById('customerSelect')?.addEventListener('change', function() {
              const selectedCustomer = this.value;
              const filteredModels = [
                  ...new Set(
                      data.filter(item => item.customer_name === selectedCustomer)
                      .map(item => item.model)
                  )
              ];
              populateDropdown('modelSelect', filteredModels);
          });

          // When model changes → load components
          document.getElementById('modelSelect')?.addEventListener('change', function() {
              handleModelOrCustomerChange();
          });

          function handleModelOrCustomerChange() {
              const model = document.getElementById('modelSelect').value;
              const customer = document.getElementById('customerSelect').value;
              console.log(customer)
              if (!model || !customer) {
                  document.getElementById('material_components').innerHTML = '';
                  return;
              }

              // Load components
              const params = new URLSearchParams({
                  model: model,
                  customer_name: customer
              });

              fetch(`api/planner/getMaterial?${params.toString()}`)
                  .then(response => response.json())
                  .then(result => {
                      console.log('data', result);

                      // responsive wrapper
                      const wrapper = document.createElement('div');
                      wrapper.className = 'table-responsive';

                      const table = document.createElement('table');
                      table.className = 'custom-hover table table-bordered table-hover table-sm';

                      table.innerHTML = `
<thead class="table-light">
    <tr>
        <th>Material No.</th>
        <th>Material Description</th>
        <th>Fuel Type</th>
        <th>PI KBN Lot Quantity</th>
        <th>PI KBN Lot Pieces</th>
        <th>Total Quantity</th>
        <th class="text-center">Action</th>
    </tr>
</thead>
<tbody>
    ${result.map((row, index) => {
        const isVios = model?.toUpperCase() === "VIOS";
        const defaultFuel = isVios ? "GAS" : "";
        return `
        <tr>
            <td class="materialNo">${row.material_no}</td>
            <td class="materialDesc" style="white-space: normal; word-wrap: break-word;">${row.material_description}</td>
            
 <td>
  <select class="form-control form-control-md fuelType" 
          style="min-width: 90px;" 
          data-index="${index}" >
      <option value="">-- Select Fuel --</option>
      <option value="GAS" ${row.fuel_type === "GAS" ? "selected" : ""}>GAS</option>
      <option value="DIESEL" ${row.fuel_type === "DIESEL" ? "selected" : ""}>DIESEL</option>
  </select>
</td>

            <td>
                <div class="d-flex align-items-center">
                    <input type="number" class="form-control form-control-sm pi_kbn_quantity" data-index="${index}" value="0" min="0" step="1" style="width: 80px;" />
                    <button type="button" class="btn btn-outline-secondary btn-sm ml-1 increaseBtn">+</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ml-1 decreaseBtn">-</button>
                </div>
            </td>

            <td>
                <input type="number" class="form-control form-control-sm pi_kbn_pieces" data-index="${index}" value="0" min="0" step="1" />
            </td>

            <td class="text-center totalQty" id="totalQty${index}">0</td>

            <td class="process" style="display:none">${row.process || ''}</td>
            <td class="assemblySection" style="display:none">${row.assembly_section || ''}</td>
          
            <td class="assemblyProcess" style="display:none">${row.assembly_process || ''}</td>
            <td class="assemblyProcesstime" style="display:none">${row.assembly_processtime || ''}</td>
            <td class="assemblyTotalprocess" style="display:none">${row.total_process || ''}</td>
            <td class="manpower" style="display:none">${row.manpower || ''}</td>

            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm deleteRowBtn">Delete</button>
            </td>
        </tr>
        `;
    }).join('')}
</tbody>
`;

                      wrapper.appendChild(table);
                      const container = document.getElementById('material_components');
                      container.innerHTML = '';
                      container.appendChild(wrapper);

                      // event listeners
                      container.querySelectorAll('.deleteRowBtn').forEach(btn => {
                          btn.addEventListener('click', function() {
                              this.closest('tr').remove();
                              updateAllTotalQuantities();
                          });
                      });

                      container.querySelectorAll('tbody tr').forEach(row => {
                          const qtyInput = row.querySelector('.pi_kbn_quantity');
                          const supplementInput = row.querySelector('.pi_kbn_pieces');
                          const totalQtyDisplay = row.querySelector('.totalQty');

                          const recalcTotal = () => {
                              const qty = parseInt(qtyInput.value) || 0;
                              const supp = parseInt(supplementInput.value) || 0;
                              totalQtyDisplay.textContent = qty * supp;
                          };

                          row.querySelector('.increaseBtn').addEventListener('click', () => {
                              qtyInput.value = (parseInt(qtyInput.value) || 0) + 10;
                              recalcTotal();
                          });

                          row.querySelector('.decreaseBtn').addEventListener('click', () => {
                              let val = parseInt(qtyInput.value) || 0;
                              if (val >= 10) qtyInput.value = val - 10;
                              recalcTotal();
                          });

                          qtyInput.addEventListener('input', recalcTotal);
                          supplementInput.addEventListener('input', recalcTotal);
                          recalcTotal();
                      });

                      function updateAllTotalQuantities() {
                          container.querySelectorAll('tbody tr').forEach(row => {
                              const qtyInput = row.querySelector('.pi_kbn_quantity');
                              const supplementInput = row.querySelector('.pi_kbn_pieces');
                              const totalQtyDisplay = row.querySelector('.totalQty');
                              totalQtyDisplay.textContent = (parseInt(qtyInput.value) || 0) * (parseInt(supplementInput.value) || 0);
                          });
                      }
                  });
          }




          document.getElementById('delivery_submit_btn')?.addEventListener('click', function() {
              const currentQty = parseFloat(document.getElementById('qty')?.value) || 0;
              const rows = document.querySelectorAll('#material_components table tbody tr');
              const results = [];
              const variant = document.getElementById('variantSelect')?.value || '';
              const customer_name = document.getElementById('customerSelect')?.value || ''; // <-- add this

              for (let i = 0; i < rows.length; i++) {
                  const row = rows[i];
                  const process = row.querySelector('.process')?.innerText.trim() || '';
                  const material_no = row.querySelector('.materialNo')?.innerText.trim() || '';
                  const material_description = row.querySelector('.materialDesc')?.innerText.trim() || '';
                  const supplementInput = row.querySelector('.supplementInput');
                  const supplementVal = parseFloat(supplementInput?.value) || 0;
                  let totalQty = currentQty + supplementVal;
                  console.log(process);
                  const assembly_section = row.querySelector('.assemblySection')?.innerText.trim() || '';
                  const sub_component = row.querySelector('.subComponent')?.innerText.trim() || '';
                  const assembly_process = row.querySelector('.assemblyProcess')?.innerText.trim() || '';
                  const assemblyTotalprocess = row.querySelector('.assemblyTotalprocess')?.innerText.trim() || '';
                  const assemblyProcesstime = row.querySelector('.assemblyProcesstime')?.innerText.trim() || '';
                  const manpower = row.querySelector('.manpower')?.innerText.trim() || '';

                  const pi_kbn_quantity = parseFloat(row.querySelector('.pi_kbn_quantity')?.value) || 0;
                  const pi_kbn_pieces = parseFloat(row.querySelector('.pi_kbn_pieces')?.value) || 0;
                  const fuelType = row.querySelector('.fuelType')?.value || '';
                  const shift = document.getElementById('shifting')?.value || '';
                  const lot_no = document.getElementById('lot')?.value || null;
                  const model = document.getElementById('modelSelect')?.value || '';
                  const date_needed = document.getElementById('date_needed')?.value || '';
                  if (!shift) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Missing Shift',
                          text: 'Please select a shift before submitting.',
                          confirmButtonColor: '#d33',
                          customClass: {
                              popup: 'swal-sm' // add your custom CSS class
                          }
                      });
                      return;
                  }
                  if (pi_kbn_quantity <= 0 || pi_kbn_pieces <= 0) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Invalid Input',
                          text: 'PI KBN Quantity and Pieces must be greater than 0.',
                          confirmButtonColor: '#d33',
                          customClass: {
                              popup: 'swal-sm' // add your custom CSS class
                          }
                      });
                      return;
                  }

                  if (!date_needed) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Missing Date Needed',
                          text: 'Please choose a “Date Needed” before submitting.',
                          confirmButtonColor: '#d33',
                          customClass: {
                              popup: 'swal-sm' // add your custom CSS class
                          }
                      });
                      return;
                  }
                  if (customer_name === 'TOYOTA' || customer_name === 'HONDA') {
                      if (pi_kbn_pieces === 0 && pi_kbn_quantity === 0) {
                          Swal.fire({
                              icon: 'error',
                              title: 'Invalid Quantity',
                              text: 'Please enter a valid quantity for at least one component.',
                              confirmButtonColor: '#d33',
                              customClass: {
                                  popup: 'swal-sm' // add your custom CSS class
                              }
                          });
                          return;
                      }
                      totalQty = pi_kbn_quantity * pi_kbn_pieces;
                  }


                  results.push({
                      material_no,
                      model,
                      material_description,
                      quantity: currentQty,
                      supplement_order: supplementInput?.value || '',
                      total_quantity: totalQty,
                      pi_kbn_quantity,
                      pi_kbn_pieces,
                      status: 'pending',
                      section: 'DELIVERY',
                      shift,
                      lot_no,
                      date_needed,
                      process,
                      assembly_section,
                      variant,
                      sub_component,
                      assembly_process,
                      assemblyTotalprocess,
                      manpower,
                      assemblyProcesstime,
                      pi_kbn_pieces,
                      pi_kbn_quantity,
                      customer_name,
                      fuel_type: fuelType
                  });
              }

              console.log('result', results);

              if (results.length === 0) {
                  Swal.fire({
                      icon: 'error',
                      title: 'No Data',
                      text: 'No valid materials with quantity found to submit.',
                      confirmButtonColor: '#d33',
                      customClass: {
                          popup: 'swal-sm' // add your custom CSS class
                      }
                  });
                  return;
              }
              Swal.fire({
                  title: 'Confirm Submission',
                  text: 'Do you want to proceed with submitting this delivery form?',
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonText: 'Yes, Confirm',
                  cancelButtonText: 'Cancel',
                  reverseButtons: true,
                  customClass: {
                      confirmButton: 'btn btn-primary',
                      cancelButton: 'btn btn-secondary',
                      popup: 'swal-sm'
                  },
                  buttonsStyling: false
              }).then((result) => {
                  if (result.isConfirmed) {
                      fetch('api/planner/submitForm', {
                              method: 'POST',
                              headers: {
                                  'Content-Type': 'application/json'
                              },
                              body: JSON.stringify(results)
                          })
                          .then(response => response.json())
                          .then(responseData => {
                              if (responseData.status === 'error') {
                                  let itemList = responseData.insufficient_items?.map(item => `
                        <li><strong>${item.material_no}</strong>: ${item.material_description}<br/><small>${item.reason}</small></li>
                    `).join('');

                                  Swal.fire({
                                      icon: 'error',
                                      title: 'Insufficient Stock',
                                      html: `
                            <p>${responseData.message}</p>
                            <ul style="text-align:left; max-height: 300px; overflow-y: auto;">${itemList}</ul>
                        `,
                                      confirmButtonColor: '#d33',
                                      customClass: {
                                          popup: 'swal-sm' // add your custom CSS class
                                      }
                                  });
                                  return;
                              }

                              Swal.fire({
                                  icon: 'success',
                                  title: 'Success',
                                  text: 'Your operation was successful!',
                                  confirmButtonColor: '#3085d6',
                                  customClass: {
                                      popup: 'swal-sm' // add your custom CSS class
                                  }
                              });

                              if (responseData?.length > 0) {
                                  document.getElementById('lot').value = parseInt(responseData[0].lot_no) + 1;
                              }
                          })
                          .catch(error => {
                              console.error('Error posting data:', error);
                              Swal.fire({
                                  icon: 'error',
                                  title: 'Network Error',
                                  text: 'Failed to post data. Please try again.',
                                  confirmButtonColor: '#d33',
                                  customClass: {
                                      popup: 'swal-sm' // add your custom CSS class
                                  }
                              });
                          });
                  }
              });

          });


      });
  </script>