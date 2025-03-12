$(document).ready(function() {

    /* =======================================
       Dictionary Grid & Search Setup
       ======================================= */
    if ($('#dictionaryGrid').length && $('#dictionaryGrid').children().length > 0) {
        // Tampilkan tombol toggle dan input search hanya jika data kamus tersedia
        $('#toggleDictionary').show();
        $('#searchInput').show();

        // Toggle dictionary grid visibility
        $('#toggleDictionary').click(function() {
            $('#dictionaryGrid').toggle();
        });

        // Fungsi pencarian
        $('#searchInput').on('input', function() {
            const query = $(this).val().trim().toLowerCase();
            const $searchResults = $('#searchResults');
            const $originalGrid = $('#dictionaryGrid');

            // Reset tampilan pencarian dan grid
            $searchResults.empty().hide();
            $originalGrid.show();

            if (query) {
                // Sembunyikan grid asli dan tampilkan hasil pencarian
                $originalGrid.hide();
                $searchResults.show();

                // Lakukan pencarian pada setiap item
                $('.dictionary-item').each(function() {
                    const $item = $(this);
                    const text = $item.find('.dictionary-text').text().toLowerCase();

                    if (text.includes(query)) {
                        // Clone item dan highlight kata kunci
                        const $clone = $item.clone();
                        const highlightedText = $clone.find('.dictionary-text').html()
                            .replace(new RegExp(`(${query})`, 'gi'), '<span class="highlight">$1</span>');

                        $clone.find('.dictionary-text').html(highlightedText);

                        // Append item yang sudah di-clone ke hasil pencarian dengan struktur grid yang sama
                        $searchResults.append(
                            `<div class="col-md-3 mb-4">
                                <ul class="list-group">${$clone.prop('outerHTML')}</ul>
                            </div>`
                        );
                    }
                });
            } else {
                // Jika tidak ada query, tampilkan grid asli dan sembunyikan hasil pencarian
                $originalGrid.show();
                $searchResults.hide();
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

    /* =======================================
       Editable Text Field
       ======================================= */
    // Tampilkan field input saat double-click pada tampilan teks
    $('.text-display').on('dblclick', function() {
        const $editable = $(this).closest('.editable');
        $editable.find('.text-display').hide();
        $editable.find('.text-edit').show().focus();
    });

    // Simpan perubahan saat field input kehilangan fokus (blur)
    $('.text-edit').on('blur', function() {
        const $editable = $(this).closest('.editable');
        const newText = $(this).val();
        const index = $editable.data('index');

        // Kirim permintaan AJAX untuk menyimpan perubahan
        $.ajax({
            url: 'update_subtitle.php',
            method: 'POST',
            data: {
                index: index,
                text: newText
            },
            success: function(response) {
                // Perbarui tampilan teks dengan response dari server
                $editable.find('.text-display').html(response).show();
                $editable.find('.text-edit').hide();
                console.log('Text updated successfully!');
            },
            error: function() {
                console.log('Failed to update text.');
            }
        });
    });

    // Tangani penekanan tombol Enter untuk menyimpan perubahan
    $('.text-edit').on('keypress', function(e) {
        if (e.which === 13) { // Jika tombol Enter ditekan
            $(this).blur(); // Memicu event blur untuk menyimpan perubahan
        }
    });

});
