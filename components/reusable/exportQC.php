<div class="modal fade" id="sectionFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter by Section, Year & Month</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Section</label>
                    <select id="modal-section-select" class="form-select"></select>
                </div>

                <div class="mb-3 d-flex gap-2">
                    <div class="flex-grow-1">
                        <label class="form-label">Year</label>
                        <select id="modal-year-select" class="form-select"></select>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label">Month</label>
                        <select id="modal-month-select" class="form-select"></select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="modal-apply-btn" class="btn btn-primary">Apply</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openSectionMonthModal(cacheObj, onApply) {
        const modalEl = document.getElementById('sectionFilterModal');
        const modalInstance = new bootstrap.Modal(modalEl);

        const secSel = document.getElementById('modal-section-select');
        const yearSel = document.getElementById('modal-year-select');
        const monSel = document.getElementById('modal-month-select');

        // Extract section-date pairs from cacheObj keys
        const data = Object.entries(cacheObj).flatMap(([key, obj]) => {
            const [section] = key.split('__');
            return Object.keys(obj).map(date => ({
                section,
                date
            }));
        });

        // Populate Section dropdown
        const sections = [...new Set(data.map(d => d.section))].sort();
        secSel.innerHTML = sections.map(s => `<option value="${s}">${s}</option>`).join('');

        // Populate Year dropdown
        const years = [...new Set(
            data
            .map(d => d.date)
            .filter(Boolean) // removes undefined or null
            .map(d => parseInt(d.split('-')[0])) // get the year part as number
            .filter(y => !isNaN(y)) // removes any invalid number
        )].sort((a, b) => a - b);

        const currentYear = new Date().getFullYear();
        yearSel.innerHTML = (years.length ? years : [currentYear])
            .map(y => `<option ${y === currentYear ? 'selected' : ''}>${y}</option>`).join('');

        // Populate Month dropdown
        monSel.innerHTML = Array.from({
            length: 12
        }, (_, i) => {
            const monthNumber = i + 1;
            const monthName = new Date(0, i).toLocaleString('default', {
                month: 'short'
            });
            const selected = monthNumber === new Date().getMonth() + 1 ? 'selected' : '';
            return `<option value="${monthNumber}" ${selected}>${monthName}</option>`;
        }).join('');

        // Handle Apply button
        document.getElementById('modal-apply-btn').onclick = () => {
            const section = secSel.value;
            const year = +yearSel.value;
            const month = String(monSel.value).padStart(2, '0'); // Format to 2-digit

            const targetKey = `${section}__${year}-${month}`;
            const filteredData = cacheObj[targetKey] || {};
            console.log('Sending export data:', {
                section,
                year,
                month: +month,
                data: filteredData
            });


            fetch('api/export/exportQCExcel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        section,
                        year,
                        month: +month,
                        data: filteredData
                    })
                })
                .then(async res => {
                    // Log raw text response (before attempting to use as Blob)
                    const contentType = res.headers.get('Content-Type');

                    if (contentType && contentType.includes('application/json')) {
                        const json = await res.json();
                        console.log('📦 JSON response:', json);
                    } else if (contentType && contentType.includes('text/plain')) {
                        const text = await res.text();
                        console.log('📝 Plain text response:', text);
                    } else if (res.ok) {
                        const blob = await res.blob();
                        const filename = res.headers.get('X-Filename') || 'Direct OK.xlsx';

                        const downloadUrl = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        URL.revokeObjectURL(downloadUrl);
                    } else {
                        console.error('❌ Response error:', res.status, res.statusText);
                    }
                })
                .catch(err => {
                    console.error('❌ Fetch failed:', err);
                });

            onApply({
                section,
                year,
                month: +month
            });
            modalInstance.hide();
        };

        modalInstance.show();
    }
</script>