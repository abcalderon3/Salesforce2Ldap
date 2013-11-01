<div>
    <h3>Configuration Parameters</h3>
    <div class='configTable'>
<?php
    App::uses('ConnectionManager', 'Model');
    try {
        $sforce = ConnectionManager::getDataSource('sforce');
    } catch (Exception $connectionError) {
        $sforce = false;
        $sfErrorMsg = $connectionError->getMessage();
        if (method_exists($connectionError, 'getAttributes')) {
            $attributes = $connectionError->getAttributes();
            if (isset($sfErrorMsg['message'])) {
                $sfErrorMsg .= '<br />' . $attributes['message'];
            }
        }
    }
    echo '<div class="config">';
        echo '<h4>Salesforce Configuration</h4>';
        if ($sforce) {
            echo '<table>';
            echo $this->Html->tableCells(array('WSDL File Name (in /app/Vendor/soapclient/)', $sforce->config['wsdl']));
            echo $this->Html->tableCells(array('User Name', $sforce->config['username']));
            echo $this->Html->tableCells(array('Password', '<input type="password" disabled=true value='.str_repeat('a',strlen($sforce->config['password'])).'>'));
            echo $this->Html->tableCells(array('Query Batch Size', $sforce->config['queryBatchSize']));
            echo $this->Html->tableCells(array('SOQL', $sforce->config['SOQL']));
            echo '</table>';
        } else {
            echo '<p class="error">';
                echo 'Salesforce configuration not found. Check that your database.php file exists and has an <strong>sforce</strong> datasource connection.';
                echo '<br /><br />';
                echo $sfErrorMsg;
            echo '</p>';
        }
    echo '</div>';
    try {
        $ldap = ConnectionManager::getDataSource('ldap');
    } catch (Exception $connectionError) {
        $ldap = false;
        $lErrorMsg = $connectionError->getMessage();
        if (method_exists($connectionError, 'getAttributes')) {
            $attributes = $connectionError->getAttributes();
            if (isset($lErrorMsg['message'])) {
                $lErrorMsg .= '<br />' . $attributes['message'];
            }
        }
    }
    echo '<div class="config">';
        echo '<h4>LDAP Configuration</h4>';
        if ($ldap) {
            echo '<table>';
            echo $this->Html->tableCells(array('Host', $ldap->config['host']));
            echo $this->Html->tableCells(array('Port', $ldap->config['port']));
            echo $this->Html->tableCells(array('TLS', '<input type="checkbox" disabled=true '. (($ldap->config['tls']) ? 'checked' : '') .'>'));
            echo $this->Html->tableCells(array('Base DN', $ldap->config['basedn']));
            echo $this->Html->tableCells(array('User Name', $ldap->config['login']));
            echo $this->Html->tableCells(array('Password','<input type="password" disabled=true value='.str_repeat('*',strlen($ldap->config['password'])).'>'));
            echo $this->Html->tableCells(array('Type', $ldap->config['type']));
            echo $this->Html->tableCells(array('LDAP Version', $ldap->config['version']));
            echo '</table>';
        } else {
            echo '<p class="error">';
                echo 'Ldap configuration not found. Check that your database.php file exists and has an <strong>ldap</strong> datasource connection.';
                echo '<br /><br />';
                echo $lErrorMsg;
            echo '</p>';
        }
    echo '</div>';
?>
    </div>
    <div>
        <h4>Sync Configuration</h4>
<?php
    echo '<table>';
    echo $this->Html->tableCells(array('Context',$syncPara['context'].','.$ldap->config['basedn']));
    echo $this->Html->tableCells(array('ObjectClass for new objects',$syncPara['ldapObjectClass']));
    echo $this->Html->tableCells(array('LDAP Attribute for Sforce Id',$syncPara['ldapSforceIdAttr']));
    echo $this->Html->tableCells(array('Generate CN?', '<input type="checkbox" disabled=true '. (($syncPara['generateCN']) ? 'checked' : '') .'>'));
    echo $this->Html->tableCells(array('Generate Uid?', '<input type="checkbox" disabled=true '. (($syncPara['generateUid']) ? 'checked' : '') .'>'));
    $syncTable = '<table style="width: auto">';
        $syncTable .= $this->Html->tableHeaders(array('Salesforce Field','&rarr;','LDAP Attribute'));
        foreach ($syncPara['syncMap'] as $key => $value) {
            $syncTable .= $this->Html->tableCells(array($value,'&rarr;',$key));
        }
    $syncTable .= '</table>';
    echo $this->Html->tableCells(array('Sync Mapping',$syncTable));
    echo '</table>';
?>
    </div>
</div>
<div>
<?php
    echo $this->Html->link('Run Sync', array('controller' => 'sforce_objects', 'action' => 'syncContacts'), array('class' => 'button'));
?>
</div>