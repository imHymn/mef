<script>
    // handleRefresh.js
    function handleRefresh(id, mainsection, assembly_section, url, location) {
        console.log(
            id,
            mainsection,
            assembly_section,
            url
        );
        if (mainsection === 'painting' || mainsection === 'finishing') {
            assembly_section = null;
        }
        scanQRCodeForUser({
            section: mainsection,
            role: ["supervisor", "administrator", "line leader"], // 🟢 allow both
            userProductionLocation: assembly_section,
            onSuccess: ({
                user_id,
                full_name,
                specific_section,
                section,
                role
            }) => {
                console.log(`✅ Authorized by ${full_name} (${user_id}) for reset:`, id, section);

                const payload = {
                    id,
                    supervisor_id: user_id,
                    supervisor_name: full_name,
                    role,
                    section: mainsection,
                    specific_section: assembly_section,
                    location
                };

                console.log("📦 Payload to reset_timein.php:", payload, id, mainsection, assembly_section, url, location);

                fetch(`api/reusable/reset_timein`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(async res => {
                        const text = await res.text();
                        console.log('🧾 Raw response:', text);

                        try {
                            const json = JSON.parse(text);

                            if (json.success) {
                                Swal.fire({
                                    title: 'Reset!',
                                    text: 'Data has been reset.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Error', json.message || 'Authorization failed.', 'error');
                            }
                        } catch (err) {
                            console.error('❌ JSON parse error:', err);
                            Swal.fire('Error', 'Invalid JSON response from server.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('❌ Fetch failed:', err);
                        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                    });
            },

            onCancel: () => {
                Swal.fire('Cancelled', 'QR scan cancelled.', 'info');
            }
        });
    }
</script>