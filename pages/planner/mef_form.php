  <script src="assets/js/sweetalert2@11.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/jquery.min.js"></script>
  <link rel="stylesheet" href="assets/css/choices.min.css" />
  <script src="assets/js/choices.min.js"></script>
  <style>
      .text-uppercase {
          text-transform: uppercase;
      }

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
                      <h6 class="card-title">MEF Form</h6>
                      <form id="delivery_form">
                          <div class="row g-3">
                              <!-- Customer Name -->
                              <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                                  <label for="customer_name" class="form-label">Customer Name</label>
                                  <select class="form-control" id="customerSelect" name="customer_name" required>
                                  </select>
                              </div>

                              <!-- Model Name -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="model" class="form-label">Model Name</label>
                                  <select class="form-control" id="modelSelect" name="model" required>
                                  </select>
                              </div>

                              <!-- Variant -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="variantWrapper" style="display:none;">
                                  <label for="variant" class="form-label">Variant</label>
                                  <input
                                      type="text"
                                      class="form-control text-uppercase"
                                      id="variantSelect"
                                      name="variant"
                                      placeholder="Enter variant" />
                              </div>

                              <!-- Lot No -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="lotWrapper" style="display:none;">
                                  <label for="lot" class="form-label">Lot No.</label>
                                  <input type="number" class="form-control text-center" id="lot" name="lot">
                              </div>

                              <!-- QTY -->


                              <!-- Shift Schedule -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="shifting" class="form-label">Shift Schedule</label>
                                  <select class="form-control" id="shifting" name="shifting" required>
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
                                  <div id="" class="d-flex align-items-center justify-content-between mb-2">
                                      <h6 class="card-title mb-0">COMPONENTS LIST</h6>
                                      <div class="input-group input-group-sm" style="width: 150px;">
                                          <button type="button" class="btn btn-outline-secondary" id="decreaseQty">-</button>
                                          <input type="number" class="form-control text-center" id="qty" name="qty" value="0">
                                          <button type="button" class="btn btn-outline-secondary" id="increaseQty">+</button>
                                      </div>
                                  </div>

                              </div>
                          </div>

                          <button id="delivery_submit_btn" type="button" class="btn btn-primary mt-3">Submit Request</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>


      <script>
          let hasLot = false;
          let hasFuel = false;
          let statuses = null;
          document.addEventListener('DOMContentLoaded', function() {
              let data = []; // Store API results
              const variantWrapper = document.getElementById('variantWrapper');
              const lotWrapper = document.getElementById('lotWrapper');

              fetch('api/reusable/getCustomerandModel')
                  .then(response => response.json())
                  .then(result => {
                      data = result.data

                      const customers = [...new Set(data.map(item => item.customer_name).filter(name => name !== 'VALERIE' && name !== 'PNR'))];
                      populateDropdown('customerSelect', customers);
                      populateDropdown('modelSelect', []);
                  })
                  .catch(error => console.error('Error fetching data:', error));

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

              document.getElementById('customerSelect')?.addEventListener('change', function() {
                  const selectedCustomer = this.value;
                  const filteredModels = [...new Set(
                      data.filter(item => item.customer_name === selectedCustomer)
                      .map(item => item.model)
                  )];
                  populateDropdown('modelSelect', filteredModels);
                  variantWrapper.style.display = 'none';
                  lotWrapper.style.display = 'none';
              });

              document.getElementById('modelSelect')?.addEventListener('change', function() {
                  handleModelOrCustomerChange();
              });

              async function handleModelOrCustomerChange() {
                  const model = document.getElementById('modelSelect').value;
                  const customer = document.getElementById('customerSelect').value;
                  const variantInput = document.getElementById('variantSelect');
                  const lotInput = document.getElementById('lot');
                  const variantWrapper = document.getElementById('variantWrapper');
                  const lotWrapper = document.getElementById('lotWrapper');
                  const container = document.getElementById('material_components');
                  const existingWrapper = container.querySelector('.table-responsive');
                  if (existingWrapper) existingWrapper.remove();

                  if (!model || !customer) {
                      variantWrapper.style.display = 'none';
                      lotWrapper.style.display = 'none';
                      hasLot = false;
                      return;
                  }

                  const filteredData = data.filter(item =>
                      item.customer_name === customer &&
                      item.model === model
                  );

                  const hasVariant = filteredData.some(item => item.variant && item.variant.trim() !== "");
                  variantWrapper.style.display = hasVariant ? 'block' : 'none';
                  if (hasVariant) variantInput.value = '';

                  statuses = filteredData.map(item => item.status);

                  hasLot = filteredData.some(item => item.lot && item.lot !== null);
                  lotWrapper.style.display = hasLot ? 'block' : 'none';
                  hasFuel = filteredData.some(item => item.fuel && item.fuel !== null);
                  if (hasLot) {
                      try {
                          const lotRes = await fetch(`api/planner/getPreviousLot?model=${encodeURIComponent(model)}&customer_name=${encodeURIComponent(customer)}`);
                          const lotData = await lotRes.json();
                          const lastLot = parseInt(lotData[0]?.lot_no) || 0;
                          lotInput.value = lastLot > 0 ? lastLot + 1 : 1;
                      } catch (err) {
                          console.error('Failed to fetch previous lot:', err);
                          lotInput.value = 1;
                      }
                  }

                  try {
                      const variant = variantInput.value.toUpperCase();

                      const params = new URLSearchParams({
                          model: model,
                          customer_name: customer,
                          variant_name: variant
                      });

                      const skuRes = await fetch(`api/planner/getMaterial?${params.toString()}`);
                      const skuData = await skuRes.json();

                      // Build table
                      const wrapper = document.createElement('div');
                      wrapper.className = 'table-responsive';

                      const table = document.createElement('table');
                      table.className = 'table table-bordered table-hover table-sm custom-hover';

                      table.innerHTML = `
            <thead class="table-light">
                <tr>
                    <th>Material No.</th>
                    <th>Material Description</th>
                       ${hasFuel ? '<th>Fuel Type</th>' : ''}
                    <th class="d-none d-md-table-cell">Supplement Order</th>
                    <th class="text-center">Total Quantity</th>
                    
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                ${skuData.map((row, index) => `
                    <tr>
                        <td class="materialNo">${row.material_no}</td>
                        <td class="materialDesc" style="white-space: normal; word-wrap: break-word;">${row.material_description}</td>
                        <td class="process d-none">${row.process || ''}</td>
                        ${hasFuel ? `
                    <td>
                        <select class="form-control form-control-md fuelType" 
                                style="min-width: 90px;" 
                                data-index="${index}">
                            <option value="">-- Select Fuel --</option>
                            <option value="GAS" ${row.fuel_type === "GAS" ? "selected" : ""}>GAS</option>
                            <option value="DIESEL" ${row.fuel_type === "DIESEL" ? "selected" : ""}>DIESEL</option>
                        </select>
                    </td>
                ` : ''}
                <td class="assemblySection d-none">${row.assembly_section || ''}</td>
                        <td class="subComponent d-none">${row.sub_component || ''}</td>
                        <td class="assemblyProcess d-none">${row.assembly_process || ''}</td>
                        <td class="assemblyTotalprocess d-none">${row.total_process || ''}</td>
                        <td class="assemblyProcesstime d-none">${row.assembly_processtime || ''}</td>
                        <td class="manpower d-none">${row.manpower || ''}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm supplementInput" 
                                data-index="${index}" value="0" min="0" step="1" placeholder="Add" />
                        </td>
                        <td class="text-center">
                            <span class="totalQty" id="totalQty${index}">0</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm deleteRowBtn">Delete</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        `;

                      wrapper.appendChild(table);
                      container.appendChild(wrapper);

                      const qtyInput = document.getElementById('qty');

                      const updateAllTotalQuantities = () => {
                          const baseQty = parseInt(qtyInput?.value) || 0;
                          wrapper.querySelectorAll('.supplementInput').forEach((input, idx) => {
                              const suppQty = parseInt(input.value) || 0;
                              const display = document.getElementById(`totalQty${idx}`);
                              if (display) display.textContent = baseQty + suppQty;
                          });
                      };
                      const attachEvents = () => {
                          wrapper.querySelectorAll('.deleteRowBtn').forEach(btn => {
                              btn.onclick = () => {
                                  btn.closest('tr').remove();
                                  wrapper.querySelectorAll('.supplementInput').forEach((inp, i) => inp.dataset.index = i);
                                  updateAllTotalQuantities();
                              };
                          });

                          wrapper.querySelectorAll('.supplementInput').forEach(input => {
                              input.addEventListener('input', updateAllTotalQuantities);
                          });

                          qtyInput?.addEventListener('input', updateAllTotalQuantities);
                      };
                      attachEvents();
                      updateAllTotalQuantities();
                  } catch (error) {
                      console.error('Error fetching components:', error);
                      showAlert('error', 'Error', 'Failed to fetch components.');
                  }
              }

              document.getElementById('delivery_submit_btn')?.addEventListener('click', function() {
                  const currentQty = parseFloat(document.getElementById('qty')?.value) || 0;
                  const rows = document.querySelectorAll('#material_components table tbody tr');
                  const results = [];
                  const variant = document.getElementById('variantSelect')?.value.toUpperCase() || '';
                  const customer = document.getElementById('customerSelect').value;
                  for (let i = 0; i < rows.length; i++) {
                      const row = rows[i];
                      const process = row.querySelector('.process')?.innerText.trim() || null;
                      const material_no = row.querySelector('.materialNo')?.innerText.trim() || '';
                      const material_description = row.querySelector('.materialDesc')?.innerText.trim() || '';
                      const supplementInput = row.querySelector('.supplementInput');
                      const supplementVal = parseFloat(supplementInput?.value) || 0;
                      const totalQty = currentQty + supplementVal;
                      const assembly_section = row.querySelector('.assemblySection')?.innerText.trim() || '';
                      const sub_component = row.querySelector('.subComponent')?.innerText.trim() || '';
                      const assembly_process = row.querySelector('.assemblyProcess')?.innerText.trim() || '';
                      const assemblyTotalprocess = row.querySelector('.assemblyTotalprocess')?.innerText.trim() || '';
                      const assemblyProcesstime = row.querySelector('.assemblyProcesstime')?.innerText.trim() || '';
                      const manpower = row.querySelector('.manpower')?.innerText.trim() || '';
                      const shift = document.getElementById('shifting')?.value;
                      const lot_no = document.getElementById('lot')?.value || 0;
                      const model = document.getElementById('modelSelect')?.value || '';
                      const date_needed = document.getElementById('date_needed')?.value || '';
                      const fuelType = row.querySelector('.fuelType')?.value || null;

                      results.push({
                          material_no,
                          model,
                          material_description,
                          quantity: currentQty,
                          supplement_order: supplementInput?.value || '',
                          total_quantity: totalQty,
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
                          fuelType,
                      });

                  }

                  if (results.length === 0) {
                      showAlert('error', 'No Data', 'No valid materials with quantity found to submit.');
                      return;
                  }

                  const lot_value = document.getElementById('lot')?.value || 1;

                  // Only show Frozen Lot warning if hasLot is true
                  const proceedWithSubmit = () => {
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
                                  if (responseData.insufficient_items?.length) {
                                      const itemList = responseData.insufficient_items.map(item => `
                        <li><strong>${item.material_no}</strong>: ${item.material_description}<br/><small>${item.reason}</small></li>
                    `).join('');
                                      showAlert('error', 'Insufficient Stock', `${responseData.message}\n\n${itemList}`);
                                  } else {
                                      showAlert('error', 'Error', responseData.message || 'Something went wrong.');
                                  }
                                  return;
                              }

                              showAlert('success', 'Success', 'Your operation was successful!');

                              if (responseData?.length > 0) {
                                  document.getElementById('lot').value = parseInt(responseData[0].lot_no) + 1;
                              }
                          })
                          .catch(error => {
                              console.error('Error posting data:', error);
                              showAlert('error', 'Network Error', 'Failed to post data. Please try again.');
                          });
                  };
                  console.log(statuses)
                  if (hasLot) {
                      Swal.fire({
                          title: 'Frozen Lot Warning',
                          html: `The delivery form is at <strong>LOT - ${lot_value}</strong>. Do you want to proceed?`,
                          icon: 'warning',
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
                          if (result.isConfirmed) proceedWithSubmit();
                      });
                  } else {
                      proceedWithSubmit();
                  }
              });


              document.getElementById('increaseQty')?.addEventListener('click', () => {
                  const qtyInput = document.getElementById('qty');
                  let val = parseInt(qtyInput?.value) || 0;
                  qtyInput.value = val + 30;
                  qtyInput.dispatchEvent(new Event('input'));
              });

              document.getElementById('decreaseQty')?.addEventListener('click', () => {
                  const qtyInput = document.getElementById('qty');
                  let val = parseInt(qtyInput?.value) || 0;
                  if (val >= 30) {
                      qtyInput.value = val - 30;
                      qtyInput.dispatchEvent(new Event('input'));
                  }
              });
          });
      </script>