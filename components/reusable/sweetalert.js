function showAlert(type, title, text) {
    Swal.fire({
        icon: type,       // 'success', 'error', 'warning', 'info', 'question'
        title: title,
        text: text,
        confirmButtonText: 'OK',
        timer: type === 'success' ? 2000 : undefined, // auto-close for success
        timerProgressBar: true
    });
}