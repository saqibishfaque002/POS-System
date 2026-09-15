<?php

$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);
$migrations = [];

foreach ($files as $file) {
    if (strpos($file, 'create_purchase_requests_table') !== false) $migrations['pr'] = $file;
    if (strpos($file, 'create_purchase_request_items_table') !== false) $migrations['pr_items'] = $file;
    
    if (strpos($file, 'create_purchase_orders_table') !== false) $migrations['po'] = $file;
    if (strpos($file, 'create_purchase_order_items_table') !== false) $migrations['po_items'] = $file;
    
    if (strpos($file, 'create_good_received_notes_table') !== false) $migrations['grn'] = $file;
    if (strpos($file, 'create_good_received_note_items_table') !== false) $migrations['grn_items'] = $file;
    
    if (strpos($file, 'create_purchase_invoices_table') !== false) $migrations['pi'] = $file;
    if (strpos($file, 'create_purchase_invoice_items_table') !== false) $migrations['pi_items'] = $file;
    
    if (strpos($file, 'create_purchase_returns_table') !== false) $migrations['prtn'] = $file;
    if (strpos($file, 'create_purchase_return_items_table') !== false) $migrations['prtn_items'] = $file;
    
    if (strpos($file, 'create_sale_invoices_table') !== false) $migrations['si'] = $file;
    if (strpos($file, 'create_sale_invoice_items_table') !== false) $migrations['si_items'] = $file;
    
    if (strpos($file, 'create_sale_returns_table') !== false) $migrations['sr'] = $file;
    if (strpos($file, 'create_sale_return_items_table') !== false) $migrations['sr_items'] = $file;
}

$masterSchema = <<<PHP
            \$table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            \$table->string('document_number')->unique();
            \$table->date('date');
            \$table->string('status')->default('draft');
            \$table->unsignedBigInteger('reference_id')->nullable();
            \$table->decimal('total_amount', 15, 2)->default(0);
            \$table->text('notes')->nullable();
            \$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
PHP;

$detailSchema = <<<PHP
            \$table->foreignId('item_id')->constrained('food_items')->cascadeOnDelete();
            \$table->decimal('quantity', 10, 2);
            \$table->decimal('unit_price', 15, 2);
            \$table->decimal('subtotal', 15, 2);
PHP;

foreach ($migrations as $key => $file) {
    $path = $dir . $file;
    $content = file_get_contents($path);
    
    if (strpos($key, '_items') === false) {
        // Master table
        $replacement = $masterSchema;
    } else {
        // Detail table
        $masterKey = str_replace('_items', '', $key);
        // Table name is plural of masterKey, let's just get it from the file name
        preg_match('/create_(.*?)_table/', $file, $matches);
        $tableName = $matches[1];
        
        $masterTableName = str_replace('_items', 's', $tableName);
        if ($tableName === 'purchase_request_items') $masterTableName = 'purchase_requests';
        if ($tableName === 'purchase_order_items') $masterTableName = 'purchase_orders';
        if ($tableName === 'good_received_note_items') $masterTableName = 'good_received_notes';
        if ($tableName === 'purchase_invoice_items') $masterTableName = 'purchase_invoices';
        if ($tableName === 'purchase_return_items') $masterTableName = 'purchase_returns';
        if ($tableName === 'sale_invoice_items') $masterTableName = 'sale_invoices';
        if ($tableName === 'sale_return_items') $masterTableName = 'sale_returns';
        
        $foreignIdName = substr($masterTableName, 0, -1) . '_id'; // e.g., purchase_request_id
        
        $replacement = "            \$table->foreignId('{$foreignIdName}')->constrained('{$masterTableName}')->cascadeOnDelete();\n" . $detailSchema;
    }
    
    $content = preg_replace('/(\$table->id\(\);)/', "$1\n" . $replacement, $content);
    file_put_contents($path, $content);
}

echo "Migrations populated successfully!";
