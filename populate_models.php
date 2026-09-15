<?php

$dir = __DIR__ . '/database/migrations/';
// Actually we need to loop over Models in app/Models/

$models = [
    'PurchaseRequest', 'PurchaseOrder', 'GoodReceivedNote', 
    'PurchaseInvoice', 'PurchaseReturn', 'SaleInvoice', 'SaleReturn'
];

foreach ($models as $modelName) {
    $masterPath = __DIR__ . '/app/Models/' . $modelName . '.php';
    $detailPath = __DIR__ . '/app/Models/' . $modelName . 'Item.php';
    
    $masterContent = file_get_contents($masterPath);
    $detailContent = file_get_contents($detailPath);
    
    // Master model
    $masterReplace = <<<PHP
    protected \$guarded = [];

    public function items() {
        return \$this->hasMany({$modelName}Item::class);
    }

    public function branch() {
        return \$this->belongsTo(Branch::class);
    }
    
    public function creator() {
        return \$this->belongsTo(User::class, 'created_by');
    }
PHP;

    $masterContent = preg_replace('/(use HasFactory;)/', "$1\n\n" . $masterReplace, $masterContent);
    file_put_contents($masterPath, $masterContent);

    // Detail model
    $foreignMethod = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
    
    $detailReplace = <<<PHP
    protected \$guarded = [];
    public \$timestamps = false;

    public function $foreignMethod() {
        return \$this->belongsTo({$modelName}::class);
    }

    public function item() {
        return \$this->belongsTo(FoodItem::class, 'item_id');
    }
PHP;

    $detailContent = preg_replace('/(use HasFactory;)/', "$1\n\n" . $detailReplace, $detailContent);
    file_put_contents($detailPath, $detailContent);
}

echo "Models populated!";
