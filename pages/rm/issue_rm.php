<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
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
      <li class="breadcrumb-item" aria-current="page">Raw Materials Warehouse</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">For Issue</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>

          <div class="row mb-3">

            <div class="col-md-3">
              <input type="text" id="filter-input" class="form-control" placeholder="Type to filter..." />
            </div>
          </div>

          <div class="table-wrapper">
            <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
              <thead>
                <tr>
                  <th style="width: 2%; text-align: center;"></th>
                  <th style="width: 10%; text-align: center;">Material No</th>
                  <th style="width: 15%; text-align: center;">Component Name</th>
                  <th style="width: 10%; text-align: center;">Quantity</th>
                  <th style="width: 25%; text-align: center;">Raw Materials</th>
                  <th style="width: 10%; text-align: center;">Action</th>
                  <th style="width: 10%; text-align: center;">Status</th>
                </tr>
              </thead>
              <tbody id="data-body"></tbody>
            </table>
          </div>


          <div id="pagination" class="mt-3 d-flex justify-content-center"></div>
        </div>
      </div>
    </div>
  </div>


  <script>
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;
    const normalizeDesc = desc => desc.replace(/\s+/g, ' ').trim();
    let model;


    function getData(model) {
      fetch('api/rm/getIssuedComponents?model=' + encodeURIComponent(model))
        .then(response => response.json())
        .then(responseData => {
          if (Array.isArray(responseData.data)) {
            renderIssuedComponentsTable(responseData.data);

            // Apply search filter
            setupSearchFilter({
              filterColumnSelector: '#filter-column',
              filterInputSelector: '#filter-input',
              data: responseData.data,
              onFilter: filtered => renderIssuedComponentsTable(filtered),
              customColumnHandler: {
                material_no: row => row.material_no ?? '',
                component_name: row => row.component_name ?? '',
                model: row => row.model ?? '',
                quantity: row => String(row.quantity ?? ''),
                status: row => row.status ?? ''
              }
            });
          } else {
            console.warn('Unexpected response:', responseData);
          }
        });
    }

    function renderIssuedComponentsTable(data) {
      const tbody = document.getElementById('data-body');
      tbody.innerHTML = '';

      if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center">No pending requests found.</td></tr>`;
        return;
      }

      const currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';

      const statusPriority = {
        'Critical': 1,
        'Minimum': 2,
        'Reorder': 3
      };

      data.sort((a, b) => {
        function effectiveStatus(item) {
          let s = item.status || 'Pending';
          if (!item.delivered_at && item.issued_at) {
            const issuedDate = new Date(item.issued_at);
            const now = new Date();
            const diffInDays = (now - issuedDate) / (1000 * 60 * 60 * 24);
            if (diffInDays >= 1) s = 'Critical';
          }
          return s;
        }
        const statusA = effectiveStatus(a);
        const statusB = effectiveStatus(b);
        return (statusPriority[statusA] ?? 99) - (statusPriority[statusB] ?? 99);
      });

      const statusStyleMap = {
        'Maximum': 'background-color: green; color: black; font-weight: bold; padding: 2px 6px; border-radius: 4px;',
        'Critical': 'background-color: red; color: black; font-weight: bold; padding: 2px 6px; border-radius: 4px;',
        'Minimum': 'background-color: orange; color: black; font-weight: bold; padding: 2px 6px; border-radius: 4px;',
        'Reorder': 'background-color: yellow; color: black; font-weight: bold; padding: 2px 6px; border-radius: 4px;'
      };


      let shownClip25 = false;
      let shownClip60 = false;

      data.forEach(item => {
        if (item.component_name === 'CLIP 25' && shownClip25) return;
        if (item.component_name === 'CLIP 60' && shownClip60) return;
        if (item.component_name === 'CLIP 25') shownClip25 = true;
        if (item.component_name === 'CLIP 60') shownClip60 = true;

        let status = item.status || 'Pending';
        if (!item.delivered_at && item.issued_at) {
          const issuedDate = new Date(item.issued_at);
          const now = new Date();
          const diffInDays = (now - issuedDate) / (1000 * 60 * 60 * 24);
          if (diffInDays >= 1) status = 'Critical';
        }

        const style = statusStyleMap[status] || '';

        const specialModels = ["MILLIARD", "APS", "KOMYO"];
        const modelKey = (item.model || item.material_no || "")
          .replace(/[0-9]/g, "")
          .toUpperCase();
        const quantity = specialModels.includes(modelKey) ?
          item.quantity :
          300 * item.usage_type;

        const rawMaterials = (() => {
          try {
            return JSON.parse(item.raw_materials || '[]'); // render everything
          } catch {
            return [];
          }
        })();


        // remove extra spaces
        const seenDescriptions = new Set();
        const uniqueMaterials = rawMaterials.filter(rm => {
          const norm = normalizeDesc(rm.material_description);
          if (seenDescriptions.has(norm)) return false;
          seenDescriptions.add(norm);
          return true;
        });


        const rawHTML = uniqueMaterials.length ? `
  <div class="raw-table-wrapper">
    <table class="table table-sm table-bordered mb-0 raw-table">
      <colgroup>
        <col style="width: 30%;">
        <col style="width: 50%;">
        <col style="width: 20%;">
      </colgroup>
      <thead>
        <tr>
          <th>No</th>
          <th>Desc</th>
          <th>Usage</th>
        </tr>
      </thead>
      <tbody>
        ${uniqueMaterials.map(rm => {
          const usage = Number(rm.usage) || 0;
          const total = specialModels.includes(item.model?.toUpperCase())
            ? quantity
            : Math.ceil(quantity / usage);

      const descriptions = rm.material_description
  ? rm.material_description.split(',').map(d => d.trim())
  : [];

return descriptions.map(desc => `
  <tr>
    <td>${highlightText(item.material_no, currentFilterQuery)}</td>
    <td>${highlightText(desc, currentFilterQuery)}</td>
    <td>${usage}</td>
  </tr>
`).join('');

        }).join('')}
      </tbody>
    </table>
  </div>
` : '<em style="font-size:12px;">None</em>';


        const info = {
          id: item.id,
          material_no: item.material_no,
          component_name: item.component_name,
          quantity,
          process_quantity: item.process_quantity ?? 300,
          stage_name: item.stage_name,
          raw_materials: item.raw_materials,
          usage: item.usage_type,
          model: item.model,
          type: item.type,
          reference_no: item.reference_no
        };

        const row = document.createElement('tr');
        row.innerHTML = `
          <td style="text-align:center;">
            <span style="cursor:pointer;" onclick="handleRefresh('${item.id}','${item.component_name}')">🗑️</span>
          </td>
          <td class="text-center">${highlightText(item.material_no, currentFilterQuery) || '-'}</td>
          <td class="text-center" style=" white-space: normal; word-wrap: break-word;">${highlightText(item.component_name, currentFilterQuery) || '-'}</td>
          <td class="text-center">
            ${specialModels.includes(item.model?.toUpperCase()) 
                ? `${highlightText(Math.round(item.quantity), currentFilterQuery)}(ORDER)` 
                : highlightText(Math.round(item.quantity), currentFilterQuery)}
          </td>
          <td class="text-center align-middle">${rawHTML}</td>
            <td class="text-center align-middle">
            <button class="btn btn-sm btn-primary deliver-btn"
              data-info="${encodeURIComponent(JSON.stringify(info))}">
              Issue
            </button>
          </td><td class="text-center" style="${style}">${highlightText(status.toUpperCase(), currentFilterQuery)}</td>
        
        `;
        tbody.appendChild(row);
      });

      attachDeliverButtonEvents();
    }



    function attachDeliverButtonEvents() {
      document.querySelectorAll('.deliver-btn').forEach(button => {
        button.addEventListener('click', function() {
          const info = JSON.parse(decodeURIComponent(this.dataset.info || '{}'));
          const {
            id,
            material_no,
            component_name,
            quantity,
            process_quantity,
            stage_name,
            raw_materials,
            model,
            usage,
            type,
            reference_no
          } = info;
          console.log('info', info)
          fetch('api/rm/getRMStocks', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                material_no,
                component_name
              })
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success' && data.rm_stocks > 0) {
                Swal.fire({
                  title: 'Ongoing Production Detected',
                  text: `There is already an ongoing production for component "${component_name}". Do you still want to issue ?`,
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonText: 'Yes, issue anyway',
                  cancelButtonText: 'Cancel',
                  customClass: {
                    popup: 'swal-sm'
                  }
                }).then(result => {
                  if (result.isConfirmed) {
                    handleIssueFlow(info);
                  }
                });
              } else if (data.status === 'success') {
                handleIssueFlow(info)
              } else {
                // Handle other errors
                console.error(data.message);
              }
            })
            .catch(error => {
              console.error('Error fetching pair info:', error);
            });







        });
      });
    }


    function handleIssueFlow(info) {
      const {
        id,
        material_no,
        component_name,
        quantity,
        process_quantity,
        stage_name,
        raw_materials,
        model,
        usage,
        type,
        reference_no
      } = info;
      console.log(info)
      const rawMaterials = (() => {
        try {
          const all = JSON.parse(raw_materials || '[]');
          return all.filter(rm => (rm.component_name || '').trim() === (component_name || '').trim());

        } catch {
          return [];
        }
      })();

      let computedQty = 0;

      const specialModels = ["MILLIARD", "APS", "KOMYO"];
      const modelKey = (model || "")
        .replace(/[0-9]/g, "")
        .toUpperCase();

      if (specialModels.includes(modelKey)) {
        computedQty = (info.quantity || 0);
      } else {
        computedQty = 300 * usage;
      }

      console.log("Computed quantity:", computedQty);

      // 🔹 Deduplicate by material_description (keep first, drop duplicates)
      function dedupeRawMaterials(materials) {
        const seen = new Set();
        return materials.filter(rm => {
          const desc = normalizeDesc(rm.material_description);
          if (seen.has(desc)) return false;
          seen.add(desc);
          return true;
        });
      }


      function buildRawMaterialList(qty) {
        if (!rawMaterials.length) return '<em>No raw materials listed.</em>';

        const dedupedMaterials = dedupeRawMaterials(rawMaterials);

        return `
  <table class="table table-sm table-bordered mt-2">
    <thead>
      <tr>
        <th style="font-size:12px;">Material No</th>
        <th style="font-size:12px;">Description</th>
        <th style="font-size:12px;">Usage</th>
        <th style="font-size:12px;">Total</th>
      </tr>
    </thead>
    <tbody>
      ${dedupedMaterials.map(rm => {
        const usage = Number(rm.usage) || 0;
        const total = ['MILLIARD','KOMYO','APS'].includes(model?.toUpperCase())
          ? qty * usage
          : Math.ceil(qty / usage);

       const descriptions = rm.material_description
  ? rm.material_description.split(',').map(d => d.trim())
  : [];

        // ✅ Render one row per description
        return descriptions.map(desc => `
          <tr>
            <td style="font-size:12px;">${rm.material_no}</td>
            <td style="font-size:12px;">${desc}</td>
            <td style="font-size:12px;">${usage}</td>
            <td style="font-size:12px;">${total}</td>
          </tr>
        `).join('');
      }).join('')}
    </tbody>
  </table>
`;

      }


      const isClip = component_name === 'CLIP 25' || component_name === 'CLIP 60';
      baseInput = '';
      if (isClip) {
        Swal.fire({
          title: `Enter quantity for ${component_name}`,
          input: 'number',
          inputLabel: 'How many items will you issue?',
          inputAttributes: {
            min: 1,
            step: 1
          },
          showCancelButton: true,
          customClass: {
            popup: 'swal-sm'
          },
          inputValidator: (value) => {
            if (!value || isNaN(value) || value <= 0) {
              return 'Please enter a valid positive number';
            }
          }
        }).then(inputResult => {
          const qty = parseInt(inputResult.value, 10);
          if (inputResult.isConfirmed && qty > 0) {
            Swal.fire({
              title: 'Confirm Issue',
              html: `
            <p>You are about to issue <strong>${qty}</strong> items for <strong>${component_name}</strong>.</p>
            <hr/>
            ${buildRawMaterialList(qty)}
          `,
              icon: 'info',
              showCancelButton: true,
              confirmButtonText: 'Proceed',
              cancelButtonText: 'Cancel',
              customClass: {
                popup: 'swal-sm'
              }
            }).then(confirmRes => {
              if (confirmRes.isConfirmed) {
                sendIssueRequest({
                  id,
                  material_no,
                  component_name,
                  quantity: qty,
                  process_quantity,
                  model,
                  stage_name,
                  type,
                  reference_no
                });
              }
            });
          }
        });
      } else {
        Swal.fire({
          title: 'Confirm Issue',
          html: `
        <p>Are you sure you want to issue these raw materials, which are equivalent to <strong>${computedQty}</strong> items of <strong>${component_name}</strong>?</p>
        <hr/>
        ${buildRawMaterialList(computedQty)}
      `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, Issue it',
          cancelButtonText: 'Change Quantity',
          customClass: {
            popup: 'swal-sm'
          }
        }).then(result => {
          if (result.isConfirmed) {
            sendIssueRequest({
              id,
              material_no,
              component_name,
              quantity: computedQty,
              process_quantity,
              model,
              stage_name,
              type,
              reference_no
            });
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
              title: 'Custom Quantity',
              input: 'number',
              inputValue: baseInput, // just as default, no min/max
              inputLabel: `Enter base quantity for ${component_name}:`,
              inputAttributes: {
                step: 1 // allow increments but no min/max restrictions
              },
              customClass: {
                popup: 'swal-sm'
              },
              showCancelButton: true,
              preConfirm: (inputVal) => {
                const customQty = parseInt(inputVal, 10);
                if (isNaN(customQty) || customQty <= 0) {
                  Swal.showValidationMessage('Please enter a valid positive number');
                  return false;
                }
                return customQty;
              }
            }).then(inputResult => {
              const customQty = inputResult.value;
              if (inputResult.isConfirmed && customQty > 0) {
                Swal.fire({
                  title: 'Confirm Custom Quantity',
                  html: `
                <p>You will issue <strong>${customQty}</strong> items for <strong>${component_name}</strong>.</p>
                <hr/>
                ${buildRawMaterialList(customQty)}
              `,
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonText: 'Proceed',
                  cancelButtonText: 'Cancel',
                  customClass: {
                    popup: 'swal-sm'
                  }
                }).then(confirmRes => {
                  if (confirmRes.isConfirmed) {
                    sendIssueRequest({
                      id,
                      material_no,
                      component_name,
                      quantity: customQty,
                      process_quantity,
                      model,
                      stage_name,
                      type,
                      reference_no
                    });
                  }
                });
              }
            });
          }
        });
      }
    }

    function sendIssueRequest(data) {
      console.log(data);
      fetch('api/rm/issueRM', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(response => {
          if (response.status === 'success') {
            Swal.fire({
              title: 'Success',
              text: response.message || 'Issued successfully.',
              icon: 'success',
              customClass: {
                popup: 'swal-sm'
              }
            }).then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire({
              title: 'Error',
              text: response.message || 'Issue failed.',
              icon: 'error',
              customClass: {
                popup: 'swal-sm'
              }
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            title: 'Error',
            text: 'Something went wrong.',
            icon: 'error',
            customClass: {
              popup: 'swal-sm'
            }
          });
        });
    }


    function handleRefresh(id, component_name) {
      Swal.fire({
        title: 'Supervisor Authorization Required',
        html: `
      <p>This will delete the raw material issue for Material No: <strong>${component_name}</strong></p>
      <input type="password" id="supervisor-code" class="swal2-input" placeholder="Enter Supervisor Authorization Code">
    `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
          popup: 'swal-sm'
        },
        preConfirm: () => {
          const code = document.getElementById('supervisor-code').value.trim();
          if (!code) {
            Swal.showValidationMessage('Authorization code is required');
            return false;
          }
          return code;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const supervisorCode = result.value;

          fetch(`api/rm/deleteIssuance`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                id,
                code: supervisorCode,
                role: userRole,
                section: userProduction,
                specific_section: userProductionLocation
              })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Deleted!',
                  text: 'The raw material issue has been deleted.',
                  icon: 'success',
                  timer: 1500,
                  showConfirmButton: false,
                  customClass: {
                    popup: 'swal-sm'
                  }
                });

              } else {
                Swal.fire({
                  title: 'Error',
                  text: data.message || 'Something went wrong.',
                  icon: 'error',
                  customClass: {
                    popup: 'swal-sm'
                  }
                });
              }
            })
            .catch(err => {
              console.error('Delete error:', err);
              Swal.fire({
                title: 'Error',
                text: 'Failed to delete.',
                icon: 'error',
                customClass: {
                  popup: 'swal-sm'
                }
              });
            });
        }
      });
    }
  </script>
  <style>
    .raw-table-wrapper {
      overflow-x: visible;
      /* or remove overflow-x */
    }

    .raw-table {
      min-width: auto;
      /* allow it to shrink naturally */
    }


    .raw-table th,
    .raw-table td {
      font-size: 12px;
      padding: 4px;
      white-space: nowrap;
      /* prevent wrapping */
      text-overflow: ellipsis;
      overflow: hidden;
    }


    @media (min-width: 768px) and (max-width: 991.98px) {
      .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        /* smooth scroll on iOS */
      }

      .table-wrapper>table {
        min-width: 1100px;
      }

      .raw-table {
        min-width: 500px;
      }

      .table-wrapper td table {
        min-width: 300px;
        /* extend nested raw table a bit */
        table-layout: fixed;
        /* keep inner columns steady */
      }

      /* Optional: shrink font a bit to fit better */
      .table-wrapper td table th,
      .table-wrapper td table td {
        font-size: 11px;
        padding: 3px;
      }
    }
  </style>