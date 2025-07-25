const userId = Nova.config('userId');
let alertShown = false;

if (userId) {
  const interval = setInterval(() => {
    fetch(`/nova-vendor/nova-data-sync/export-status/${userId}`)
      .then(res => res.json())
      .then(data => {
        console.log('Export status:', data);

        if (data.done && !alertShown) {
          alertShown = true; // supaya tidak muncul lagi
          Nova.success('Export finish!');

          // Clear cache untuk export berikutnya
          fetch(`/nova-vendor/nova-data-sync/export-status/${userId}?clear=true`)
            .then(() => {
              // Reset alertShown setelah 3 detik agar bisa muncul lagi jika ada export baru
              setTimeout(() => {
                alertShown = false;
              }, 3000);
            });
        }
      })
      .catch(error => {
        console.error('Error polling export status:', error);
      });
  }, 1000);
}