<div class="container-fluid">
    <h3 class="text-center mt-4">Current Dictionary</h3>
    <div class="text-center">
        <button id="toggleDictionary" class="btn btn-primary mt-3 mb-3">Expand/Collapse Dictionary</button>
    </div>

    <div id="dictionaryGrid" class="row" style="display: none;">
        <?php
        $dictionary = $_SESSION['dictionary'];
        $chunkedDictionary = array_chunk($dictionary, ceil(count($dictionary) / 4), true); // Bagi dictionary menjadi 6 bagian
        ?>
        <?php foreach ($chunkedDictionary as $column): ?>
            <div class="col-md-3"> <!-- 6 kolom (12 / 6 = 2) -->
                <ul class="list-group">
                    <?php foreach ($column as $key => $value): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($key) ?> => <?= htmlspecialchars($value) ?>
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