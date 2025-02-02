$(document).ready(function() {

    // Toggle dictionary grid
    $('#toggleDictionary').click(function() {
        $('#dictionaryGrid').toggle();
    });
    
    // Handle double-click to show input field
    $('.text-display').on('dblclick', function() {
        const $editable = $(this).closest('.editable');
        $editable.find('.text-display').hide();
        $editable.find('.text-edit').show().focus();
    });

    // Handle blur event to save changes
    $('.text-edit').on('blur', function() {
        const $editable = $(this).closest('.editable');
        const newText = $(this).val();
        const index = $editable.data('index');

        // Send AJAX request to save changes
        $.ajax({
            url: 'update_subtitle.php',
            method: 'POST',
            data: {
                index: index,
                text: newText
            },
            success: function(response) {
                // Update the display text with the highlighted response
                $editable.find('.text-display').html(response).show();
                $editable.find('.text-edit').hide();
                console.log('Text updated successfully!');
            },
            error: function() {
                console.log('Failed to update text.');
            }
        });
    });

    // Handle Enter key to save changes
    $('.text-edit').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $(this).blur(); // Trigger blur event to save changes
        }
    });

    const $originalGrid = $('#dictionaryGrid');
    const $searchResults = $('#searchResults');

    // Fungsi pencarian
    $('#searchInput').on('input', function() {
        const query = $(this).val().trim().toLowerCase();
        $searchResults.empty().hide();
        $originalGrid.show();

        if (query) {
            $originalGrid.hide();
            $searchResults.show();

            // Cari di semua item
            $('.dictionary-item').each(function() {
                const $item = $(this);
                const text = $item.find('.dictionary-text').text().toLowerCase();

                if (text.includes(query)) {
                    // Clone item asli dan tambahkan highlight
                    const $clone = $item.clone();
                    const highlightedText = $clone.find('.dictionary-text').html()
                        .replace(new RegExp(`(${query})`, 'gi'), '<span class="highlight">$1</span>');

                    $clone.find('.dictionary-text').html(highlightedText);
                    $searchResults.append(
                        `<div class="col-12">` +
                        `<ul class="list-group">${$clone.prop('outerHTML')}</ul>` +
                        `</div>`
                    );
                }
            });
        } else {
            $originalGrid.show();
            $searchResults.hide();
        }
    });

    // Tombol clear search
    $('#clearSearch').click(function() {
        $('#searchInput').val('').trigger('input');
    });
});