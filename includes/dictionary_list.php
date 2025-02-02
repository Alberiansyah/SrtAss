<!-- di dalam dictionary_list.php -->
<div class="container-fluid">
    <h3 class="text-center mt-2">Current Dictionary</h3>
    <div class="text-center">
        <button id="toggleDictionary" class="btn btn-primary mt-3 mb-3">Expand/Collapse Dictionary</button>
    </div>

    <!-- Search Bar -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Cari kata atau terjemahan...">
            </div>
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