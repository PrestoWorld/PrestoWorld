<div class="presto-table-container">
    <table class="presto-table">
        <thead>
            <tr>
                <?php foreach ($columns as $slug => $label): ?>
                    <th class="col-<?php echo $slug; ?>">
                        <?php echo $label; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <?php foreach (array_keys($columns) as $slug): ?>
                        <td>
                            <?php echo $item[$slug] ?? ''; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
