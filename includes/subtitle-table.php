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
            <?php
            $currentFileIndex = $currentFileIndex ?? null;
            $logFile = null;

            // Jika dalam mode batch, dapatkan nama file log spesifik
            if (isset($currentFileIndex) && isset($_SESSION['batch_files'][$currentFileIndex]['log_file'])) {
                $logFile = $_SESSION['batch_files'][$currentFileIndex]['log_file'];
            }

            $no = 1;
            foreach ($subtitles as $subtitleIndex => $subtitle): ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= convertTimeToAss($subtitle['start']); ?></td>
                    <td><?= convertTimeToAss($subtitle['end']); ?></td>
                    <td>
                        <?php
                        // Panggil fungsi highlight dengan parameter log file spesifik
                        echo highlightIndonesiaWords($subtitle['text'], $no, $logFile);
                        ?>
                    </td>
                    <td>
                        <?php if (isset($currentFileIndex)) : ?>
                            <div class="editable" data-index="<?= $currentFileIndex . '-' . $subtitleIndex ?>">
                            <?php else: ?>
                                <div class="editable" data-index="<?= $subtitleIndex ?>">
                                <?php endif; ?>
                                <?= replaceWords($subtitle['text'], true) ?>
                                <input type="text" class="text-edit" style="display: none;" value="<?= htmlspecialchars(strip_tags(replaceWords($subtitle['text'], false))) ?>">
                                </div>
                    </td>
                </tr>
                <?php $no++; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>