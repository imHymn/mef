<style>
    .highlight {
        background-color: yellow;
        font-weight: bold;
    }
</style>

<script>
    let currentFilterQuery = '';

    function highlightText(text, searchTerm) {
        // Always coerce to string (handles null/undefined/numbers)
        const s = (text ?? '').toString();

        if (!searchTerm) return s;

        // Escape regex metacharacters in the search term
        const escaped = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        try {
            const re = new RegExp(escaped, 'gi');
            return s.replace(re, '<span class="highlight">$&</span>');
        } catch {
            // If anything goes wrong (rare), just return the plain string
            return s;
        }
    }

    function setupSearchFilter({
        filterInputSelector,
        data,
        onFilter,
        searchableFields = []
    }) {
        const input = document.querySelector(filterInputSelector);
        if (!input) return;

        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();

            let filtered = data;
            if (query) {
                filtered = data.filter(item => {
                    if (searchableFields.length > 0) {
                        // ✅ Only check specific properties
                        return searchableFields.some(field =>
                            (item[field] ?? '').toString().toLowerCase().includes(query)
                        );
                    } else {
                        // fallback: check all properties
                        return Object.values(item).some(value =>
                            (value ?? '').toString().toLowerCase().includes(query)
                        );
                    }
                });
            }

            onFilter(filtered, query);
        });
    }
</script>