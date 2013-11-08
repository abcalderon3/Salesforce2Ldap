<div>
<?php
    echo '<p style="text-align: right;">';
    echo $this->Html->link('Back to Configuration', array('controller' => 'sforce_objects', 'action' => 'index'), array('class' => 'button'));
    echo '</p>';
?>
    <h3>Results</h3>
    <h4>Created:</h4>
<?php
    if (!empty($syncResults['create'])) {
        $this->Html->getSyncResultsTable($syncResults['create']);
    } else {
        echo '<p class="notice success">No records were created.</p>';
    }
?>
    <h4>Updated:</h4>
<?php
    if (!empty($syncResults['update'])) {
        $this->Html->getSyncResultsTable($syncResults['update']);
    } else {
        echo '<p class="notice success">No records were updated.</p>';
    }
?>
    <h4>Deleted:</h4>
<?php
    if (!empty($syncResults['delete'])) {
        $this->Html->getSyncResultsTable($syncResults['delete']);
    } else {
        echo '<p class="notice success">No records were deleted.</p>';
    }
?>
        <h4>Left Unchanged:</h4>
<?php
    if (!empty($syncResults['unchanged'])) {
        $this->Html->getSyncResultsTable($syncResults['unchanged']);
    } else {
        echo '<p class="notice success">No records were deleted.</p>';
    }
?>
</div>