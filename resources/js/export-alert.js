const userId = Nova.config('userId');

if (userId) {
  // Step 1: Ambil data tanpa mark
  fetch(`/nova-vendor/nova-data-sync/export-alerts/${userId}`)
    .then(res => res.json())
    .then(exports => {
      if (exports.length > 0) {
        // Tampilkan alert
        exports.forEach(item => {
          const message = item.filename
            ? `Export finished`
            : `Export finished (no file)`;

          Nova.success(message);
        });

        // Step 2: Panggil lagi dengan mark=1 (masih GET)
        fetch(`/nova-vendor/nova-data-sync/export-alerts/${userId}?mark=1`);
      }
    })
    .catch(err => {
      console.error('Error checking export alerts:', err);
    });
}