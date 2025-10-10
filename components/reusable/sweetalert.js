function showAlert(type, title, text) {
    return Promise.resolve(
        Swal.fire({
            icon: type,
            title: title,
            text: text,
            confirmButtonText: 'OK',
            timer: type === 'success' ? 2000 : undefined
        })
    );
}
