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
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="variant" class="form-label">Variant</label>
                                  <input
                                      type="text"
                                      class="form-control text-uppercase"
                                      id="variantSelect"
                                      name="variant"
                                      placeholder="Enter variant"
                                      required />
                              </div>

                              <!-- Lot No -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                  <label for="lot" class="form-label">Lot No.</label>
                                  <input type="number" class="form-control text-center" id="lot" name="lot">
                              </div>

                              <!-- QTY -->
                              <div class="col-12 col-sm-6 col-md-3 col-lg-2 ">
                                  <label for="qty" class="form-label">QTY</label>
                                  <div class="input-group">
                                      <button type="button" class="btn btn-outline-secondary" id="increaseQty">+</button>
                                      <input type="number" class="form-control text-center" id="qty" name="qty" value="0" readonly>
                                      <button type="button" class="btn btn-outline-secondary" id="decreaseQty">-</button>
                                  </div>
                              </div>

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
                                  <h6 class="card-title">COMPONENTS LIST</h6>
                              </div>
                          </div>

                          <button id="delivery_submit_btn" type="button" class="btn btn-primary mt-3">Submit Request</button>
                      </form>
                  </div>
              </div>
          </div>
      </div>


      <script>
          document.addEventListener("DOMContentLoaded", function() {
              const drawer = document.getElementById('modelDrawer');
              const openBtn = document.getElementById('openDrawer');
              const closeBtn = document.getElementById('closeDrawer');

              openBtn.addEventListener('click', () => {
                  drawer.classList.add('open');
                  openBtn.classList.add('rotate');
                  openBtn.style.display = 'none'; // hide when drawer opens
              });

              closeBtn.addEventListener('click', () => {
                  drawer.classList.remove('open');
                  openBtn.classList.remove('rotate');
                  openBtn.style.display = 'block'; // show again
              });

          });
          document.addEventListener('DOMContentLoaded', function() {
              let data = []; // Store API results for later filtering

              // Fetch all customer/model/variant data
              fetch('api/reusable/getCustomerandModel')
                  .then(response => response.json())
                  .then(result => {
                      data = result.data;
                      console.log(data);


                      data = result.data.filter(item => item.status === null);

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

              // When model changes → filter variants
              document.getElementById('modelSelect')?.addEventListener('change', function() {
                  const selectedCustomer = document.getElementById('customerSelect').value;
                  const selectedModel = this.value;
                  const filteredVariants = [
                      ...new Set(
                          data.filter(item =>
                              item.customer_name === selectedCustomer &&
                              item.model === selectedModel
                          ).map(item => item.variant)
                      )
                  ];

                  handleModelOrCustomerChange();
              });

              async function handleModelOrCustomerChange() {
                  const model = document.getElementById('modelSelect').value;
                  const customer = document.getElementById('customerSelect').value;
                  const variant = document.getElementById('variantSelect').value.toUpperCase();

                  const container = document.getElementById('material_components');
                  container.innerHTML = '';

                  if (!model || !customer) return;

                  try {
                      // Get next lot number
                      const lotRes = await fetch(`api/planner/getPreviousLot?model=${encodeURIComponent(model)}`);
                      const lotData = await lotRes.json();
                      let lotNo = parseInt(lotData[0]?.lot_no) || 0;
                      if (model == 'GRAB RAIL') {
                          lotNo = 0;
                      } else {
                          document.getElementById('lot').value = lotNo > 0 ? lotNo + 1 : 1;

                      }


                      // Fetch components
                      const params = new URLSearchParams({
                          model: model,
                          customer_name: customer,
                          variant_name: variant
                      });
                      const skuRes = await fetch(`api/planner/getMaterial?${params.toString()}`);
                      const skuData = await skuRes.json();

                      if (!skuData.length) {
                          container.innerHTML = `<div class="alert alert-warning p-2">No components found for this selection.</div>`;
                          return;
                      }

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
          <th class="d-none d-md-table-cell">Supplement Order</th>
          <th class="text-center">Total Quantity</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        ${skuData.map((row, index) => `
          <tr>
            <td class="materialNo">${row.material_no}</td>
           <td class="materialDesc" style="white-space: normal; word-wrap: break-word;">
  ${row.material_description}
</td>

            <td class="process d-none">${row.process || ''}</td>
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

                      // Function to update totals
                      const updateAllTotalQuantities = () => {
                          const baseQty = parseInt(qtyInput?.value) || 0;
                          wrapper.querySelectorAll('.supplementInput').forEach((input, idx) => {
                              const suppQty = parseInt(input.value) || 0;
                              const display = document.getElementById(`totalQty${idx}`);
                              if (display) display.textContent = baseQty + suppQty;
                          });
                      };

                      // Event listeners
                      const attachEvents = () => {
                          wrapper.querySelectorAll('.deleteRowBtn').forEach(btn => {
                              btn.onclick = () => {
                                  btn.closest('tr').remove();
                                  // Re-index supplement inputs
                                  wrapper.querySelectorAll('.supplementInput').forEach((inp, i) => inp.dataset.index = i);
                                  // Recalculate totals
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
                      console.error('Error:', error);
                      Swal.fire({
                          title: 'Error',
                          text: 'Failed to fetch data.',
                          icon: 'error',
                          customClass: {
                              popup: 'swal-sm'
                          }
                      });

                  }
              }



              document.getElementById('delivery_submit_btn')?.addEventListener('click', function() {
                  const currentQty = parseFloat(document.getElementById('qty')?.value) || 0;
                  const rows = document.querySelectorAll('#material_components table tbody tr');
                  const results = [];
                  const variant = document.getElementById('variantSelect')?.value.toUpperCase() || ''; // Get the selected variant
                  console.log(rows);

                  for (let i = 0; i < rows.length; i++) {
                      const row = rows[i];
                      const process = row.querySelector('.process')?.innerText.trim() || null;
                      const material_no = row.querySelector('.materialNo')?.innerText.trim() || '';
                      const material_description = row.querySelector('.materialDesc')?.innerText.trim() || '';
                      const supplementInput = row.querySelector('.supplementInput');
                      const supplementVal = parseFloat(supplementInput?.value) || 0;
                      const totalQty = currentQty + supplementVal;
                      const assembly_section = row.querySelector('.assemblySection')?.innerText.trim() || '';
                      const sub_component = row.querySelector('.subComponent')?.innerText.trim() || ''; // Capture Sub Component
                      const assembly_process = row.querySelector('.assemblyProcess')?.innerText.trim() || ''; // Capture Assembly Process
                      const assemblyTotalprocess = row.querySelector('.assemblyTotalprocess')?.innerText.trim() || ''; // Capture Assembly Process
                      const assemblyProcesstime = row.querySelector('.assemblyProcesstime')?.innerText.trim() || ''; // Capture Assembly Process
                      const manpower = row.querySelector('.manpower')?.innerText.trim() || ''; // Capture Assembly Process

                      if (material_no === '' || material_description === '' || totalQty <= 0) continue;

                      const shift = document.getElementById('shifting')?.value;
                      const lot_no = document.getElementById('lot')?.value || 0;
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

                      if (!date_needed) {
                          Swal.fire({
                              icon: 'error',
                              title: 'Missing Date Needed',
                              text: 'Please choose a “Date Needed” before submitting.',
                              confirmButtonColor: '#d33',
                              customClass: {
                                  popup: 'swal-sm'
                              }
                          });
                          return;
                      }
                      if (model !== 'GRAB RAIL') {
                          if (!variant) {
                              Swal.fire({
                                  icon: 'error',
                                  title: 'Missing Variant',
                                  text: 'Please select a variant before submitting.',
                                  confirmButtonColor: '#d33',
                                  customClass: {
                                      popup: 'swal-sm'
                                  }
                              });
                              return;
                          }
                      }


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
                          assemblyProcesstime
                      });
                  }
                  console.log(results)
                  if (results.length === 0) {
                      Swal.fire({
                          icon: 'error',
                          title: 'No Data',
                          text: 'No valid materials with quantity found to submit.',
                          confirmButtonColor: '#d33',
                          customClass: {
                              popup: 'swal-sm' // add your custom CSS class
                          },
                      });
                      return;
                  }

                  const lot_value = document.getElementById('lot')?.value || 1;

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