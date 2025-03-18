<div class="container-fluid">
    <h3 class="text-center">Current Dictionary</h3>
    <div class="text-center">
        <button id="toggleDictionary" class="btn btn-primary mt-3 mb-3">
            <i class="fas fa-caret-right mx-1"></i> Expand/Collapse Dictionary
        </button>
    </div>

    <?php
    $dictionary = $_SESSION['dictionary'] ?? [];
    $itemsPerPage = 40; // Jumlah item per halaman
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $totalItems = count($dictionary);
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Pastikan currentPage tidak melebihi totalPages
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    } elseif ($currentPage < 1) {
        $currentPage = 1;
    }

    // Potong dictionary untuk halaman saat ini
    $offset = ($currentPage - 1) * $itemsPerPage;
    $pagedDictionary = array_slice($dictionary, $offset, $itemsPerPage, true);

    // Check if the dictionary is empty
    if (empty($pagedDictionary)) {
        echo '<div class="alert alert-warning text-center">No words in dictionary. Please add some words first.</div>';
    } else {
        ksort($pagedDictionary, SORT_STRING | SORT_FLAG_CASE);
        $chunkedDictionary = array_chunk($pagedDictionary, max(1, ceil(count($pagedDictionary) / 4)), true);
    ?>
        <!-- Only show search input and results if the dictionary has words -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-7">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control rounded-pill" placeholder="Find a word...">
                </div>
            </div>
        </div>

        <div id="searchResults" class="row" style="display: none;"></div>

        <div id="dictionaryGrid" class="row" style="display: none;">
            <?php foreach ($chunkedDictionary as $column): ?>
                <div class="col-md-3 mb-4">
                    <ul class="list-group">
                        <?php foreach ($column as $key => $value): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center dictionary-item">
                                <span class="dictionary-text">
                                    <strong type="original"><?= htmlspecialchars($key) ?></strong>→<strong type="convert"><?= htmlspecialchars($value) ?></strong>
                                </span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="remove_from_dictionary" value="<?= htmlspecialchars($key) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <nav aria-label="Dictionary pagination" id="paginationControls" style="display: none;">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="<?= $currentPage - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="<?= $currentPage + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php } ?>
</div>