<!-- di dalam dictionary_list.php -->
<div class="container-fluid">
    <h3 class="text-center mt-2">Current Dictionary</h3>
    <div class="text-center">
        <button id="toggleDictionary" class="btn btn-primary mt-3 mb-3">Expand/Collapse Dictionary</button>
    </div>

    <!-- Search Bar -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <input type="text" id="searchInput" class="form-control rounded-pill" placeholder="Find a word...">

        </div>
    </div>

    <!-- Container untuk hasil pencarian -->
    <div id="searchResults" class="row" style="display: none;"></div>

    <!-- Grid asli (akan disembunyikan saat pencarian) -->
    <div id="dictionaryGrid" class="row" style="display: none;">
        <?php
        $dictionary = $_SESSION['dictionary'] ?? [];
        ksort($dictionary, SORT_STRING | SORT_FLAG_CASE);
        $chunkedDictionary = array_chunk($dictionary, ceil(count($dictionary) / 4), true);
        ?>
        <?php foreach ($chunkedDictionary as $column): ?>
            <div class="col-md-3">
                <ul class="list-group">
                    <?php foreach ($column as $key => $value): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center dictionary-item">
                            <span class="dictionary-text">
                                <?= htmlspecialchars($key) ?> => <?= htmlspecialchars($value) ?>
                            </span>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="remove_from_dictionary" value="<?= htmlspecialchars($key) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        const $originalGrid = $('#dictionaryGrid');
        const $searchResults = $('#searchResults');
        let isGridExpanded = false; // Variabel untuk menyimpan state expand/collapse

        // Simpan state expand/collapse saat tombol toggle diklik
        $('#toggleDictionary').click(function() {
            isGridExpanded = $originalGrid.is(':visible');
        });

        // Fungsi pencarian
        $('#searchInput').on('input', function() {
            const query = $(this).val().trim().toLowerCase();
            $searchResults.empty().hide();

            if (query) {
                // Saat ada query, sembunyikan grid asli dan tampilkan hasil pencarian
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
                // Saat query dikosongkan, kembalikan ke state sebelumnya
                $searchResults.hide();
                if (isGridExpanded) {
                    $originalGrid.show(); // Tampilkan grid jika sebelumnya expand
                } else {
                    $originalGrid.hide(); // Sembunyikan grid jika sebelumnya collapse
                }
            }
        });

        // Tombol clear search
        $('#clearSearch').click(function() {
            $('#searchInput').val('').trigger('input');
        });
    });
</script>

<style>
    .highlight {
        background-color: #ffeb3b;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: bold;
    }

    #searchResults .list-group-item {
        border-radius: 0.25rem;
        margin-bottom: 5px;
    }

    #searchInput {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    #clearSearch {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
</style>