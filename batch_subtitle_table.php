<div class="container-fluid">
    <table class="table table-bordered mt-4 mb-3">
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
            foreach ($subtitles as $subtitleIndex => $subtitle): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= convertTimeToAss($subtitle['start']); ?></td>
                    <td><?= convertTimeToAss($subtitle['end']); ?></td>
                    <td><?= htmlspecialchars($subtitle['text']); ?></td>
                    <td>
                        <!-- Gunakan format data-index "currentFileIndex-subtitleIndex" -->
                        <div class="editable" data-index="<?= $currentFileIndex . '-' . $subtitleIndex ?>">
                            <?php
                            $modifiedText = replaceWords($subtitle['text'], true);
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