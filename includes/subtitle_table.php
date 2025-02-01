<div class="container-fluid">
    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Original Text</th>
                <th>Modified Text</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($subtitles as $index => $subtitle): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= convertTimeToAss($subtitle['start']); ?></td>
                    <td><?= convertTimeToAss($subtitle['end']); ?></td>
                    <td><?= htmlspecialchars($subtitle['text']); ?></td>
                    <td>
                        <div class="editable" data-index="<?= $index ?>">
                            <?php
                            $modifiedText = replaceWords($subtitle['text'], true); // Terapkan highlight
                            ?>
                            <span class="text-display" data-original-text="<?= htmlspecialchars($subtitle['text']) ?>">
                                <?= $modifiedText ?>
                            </span>
                            <input type="text" class="text-edit" style="display: none;" value="<?= htmlspecialchars(strip_tags($modifiedText)) ?>">
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>