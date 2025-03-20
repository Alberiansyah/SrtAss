$(document).ready(function() {

    /* =======================================
       Dictionary Grid & Search Setup
       ======================================= */

    let isDictionaryExpanded = false; // Status expand dictionary
    if ($('#dictionaryGrid').length && $('#dictionaryGrid').children().length > 0) {
        // Tampilkan tombol toggle dan input search hanya jika data kamus tersedia
        $('#toggleDictionary').show();
        $('#searchInput').show();

        // Toggle dictionary grid visibility
        $('#toggleDictionary').click(function() {        
            isDictionaryExpanded = !isDictionaryExpanded; // Toggle status
            $('#dictionaryGrid').toggle();
            $('#paginationControls').toggle(); // Tampilkan/sembunyikan pagination
        });

        // Fungsi pencarian
        $('#searchInput').on('input', function() {
            const query = $(this).val().trim().toLowerCase();
            const $searchResults = $('#searchResults');
            const $originalGrid = $('#dictionaryGrid');
            const $paginationControls = $('#paginationControls');

            // Reset tampilan pencarian dan grid
            $searchResults.empty().hide();
            $originalGrid.hide(); // Selalu sembunyikan dictionary saat pencarian aktif
            $paginationControls.hide(); // Selalu sembunyikan pagination saat pencarian aktif

            if (query) {
                // Tampilkan hasil pencarian
                $searchResults.show();

                // Lakukan pencarian pada seluruh dictionary
                const results = [];
                for (const [key, value] of Object.entries(fullDictionary)) {
                    if (key.toLowerCase().includes(query) || value.toLowerCase().includes(query)) {
                        results.push({ key, value });
                    }
                }

                // Tampilkan hasil pencarian
                if (results.length > 0) {
                    const chunkedResults = chunkArray(results, 4); // Bagi hasil menjadi 4 kolom
                    let html = '';

                    chunkedResults.forEach((column) => {
                        html += '<div class="col-md-3 mb-4"><ul class="list-group">';
                        column.forEach((item) => {
                            // Highlight kata kunci yang ditemukan
                            const highlightedKey = highlightText(item.key, query);
                            const highlightedValue = highlightText(item.value, query);

                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center dictionary-item">
                                    <span class="dictionary-text">
                                        <strong type="original">${highlightedKey}</strong> → <strong type="convert">${highlightedValue}</strong>
                                    </span>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="remove_from_dictionary" value="${item.key}">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </li>
                            `;
                        });
                        html += '</ul></div>';
                    });

                    $searchResults.html(`${html}`);
                } else {
                    $searchResults.html('<div class="col-md-12"><ul class="list-group "><div class="alert alert-warning text-center">No results found.</div></div>');
                }
            } else {
                // Jika tidak ada query, sembunyikan hasil pencarian
                $searchResults.hide();

                // Kembalikan tampilan dictionary dan pagination sesuai status expand
                if (isDictionaryExpanded) {
                    $originalGrid.show();
                    $paginationControls.show();
                }
            }
        });

        // Clear search functionality
        $('#clearSearch').click(function() {
            $('#searchInput').val('').trigger('input');
        });
    } else {
        // Sembunyikan tombol toggle dan input search jika data kamus tidak tersedia
        $('#toggleDictionary').hide();
        $('#searchInput').hide();
    }

    // Fungsi untuk membagi array menjadi beberapa bagian
    function chunkArray(array, chunks) {
        const result = [];
        const chunkSize = Math.ceil(array.length / chunks);
        for (let i = 0; i < array.length; i += chunkSize) {
            result.push(array.slice(i, i + chunkSize));
        }
        return result;
    }

    // Fungsi untuk menambahkan highlight pada teks
    function highlightText(text, query) {
        if (!query) return text; // Jika tidak ada query, kembalikan teks asli
        const regex = new RegExp(`(${query})`, 'gi'); // Buat regex untuk mencari kata kunci
        return text.replace(regex, '<span class="highlight">$1</span>'); // Tambahkan highlight
    }

    /* =======================================
       Editable Text Field
       ======================================= */
    // Tampilkan field input saat double-click pada tampilan teks
    var updateUrl = $('body').hasClass('batchConversion') ? 'update-subtitle-batch.php' : 'update-subtitle.php';
    
    // Handle double-click untuk menampilkan input edit
    $('.text-display').on('dblclick', function() {
        const $editable = $(this).closest('.editable');
        const originalText = $(this).data('original-text'); // Use the original text stored in data attribute
        $editable.find('.text-display').hide();
        $editable.find('.text-edit').val(originalText).show().focus(); // Set value to original text
    });
    
    // Handle blur untuk menyimpan perubahan
    $('.text-edit').on('blur', function() {
        const $editable = $(this).closest('.editable');
        const newText = $(this).val(); // Ambil teks yang diubah dari input
        const index = $editable.data('index'); // Format: "fileIndex-subtitleIndex" untuk batch, atau angka untuk single
    
        $.ajax({
            url: updateUrl,
            method: 'POST',
            data: {
                index: index,
                text: newText // Kirim teks yang diubah ke server
            },
            success: function(response) {
                // Perbarui tampilan dengan teks yang sudah di-highlight dari server
                $editable.find('.text-display').html(response).show();
                $editable.find('.text-edit').hide();
                console.log('Text updated successfully!');
            },
            error: function() {
                console.log('Failed to update text.');
            }
        });
    });
    
    // Handle tombol Enter untuk menyimpan perubahan
    $('.text-edit').on('keypress', function(e) {
        if (e.which === 13) { // Jika tombol Enter ditekan
            $(this).blur(); // Memicu event blur untuk menyimpan perubahan
        }
    });

    $(document).ready(function() {
        // Tambahkan efek hover pada tombol pagination
        $('.page-item').hover(function() {
            $(this).addClass('hover');
        }, function() {
            $(this).removeClass('hover');
        });
    });

    // Handle pagination clicks
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault(); // Mencegah reload halaman
        const page = $(this).data('page'); // Ambil nomor halaman dari atribut data-page

        // Kirim permintaan AJAX untuk memuat halaman baru
        $.ajax({
            url: window.location.href, // URL saat ini
            method: 'GET',
            data: { page: page }, // Kirim parameter page
            success: function(response) {
                // Parse response untuk mengambil bagian dictionary dan pagination
                const $response = $(response);
                const $newDictionaryGrid = $response.find('#dictionaryGrid').html();
                const $newPaginationControls = $response.find('#paginationControls').html();

                // Perbarui konten di halaman saat ini
                $('#dictionaryGrid').html($newDictionaryGrid);
                $('#paginationControls').html($newPaginationControls);

                // Pastikan dictionary dan pagination tetap terlihat
                $('#dictionaryGrid').show();
                $('#paginationControls').show();
            },
            error: function() {
                console.log('Failed to load page.');
            }
        });
    });

    /* =======================================
       Download Batch Form
       ======================================= */
    // Event listener untuk form download batch
    $('form.download-batch').on('submit', function(e) {
        let $form = $(this);
        let action = $form.attr('action');
        
        // Buat nilai acak (timestamp + random) agar selalu unik
        let randomValue = new Date().getTime() + '_' + Math.floor(Math.random() * 1000000);

        // Cek apakah action sudah mengandung '?'
        if (action.indexOf('?') === -1) {
            action += '?rand=' + randomValue;
        } else {
            action += '&rand=' + randomValue;
        }

        // Set action baru
        $form.attr('action', action);
    });

});
