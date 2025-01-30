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
            foreach ($subtitles as $subtitle): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= convertTimeToAss($subtitle['start']); ?></td>
                    <td><?= convertTimeToAss($subtitle['end']); ?></td>
                    <td><?= htmlspecialchars($subtitle['text']); ?></td>
                    <td>
                        <?php
                        $modifiedText = replaceWords($subtitle['text']);
                        if ($modifiedText !== $subtitle['text']) {
                            echo '<span style="background-color: #00ff33;">' . htmlspecialchars($modifiedText) . '</span>';
                        } else {
                            echo htmlspecialchars($modifiedText);
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>