<?php

use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function getModels($dir) {
    $models = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }
        if ($file->getExtension() === 'php') {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch) &&
                preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                $className = $namespaceMatch[1] . '\\' . $classMatch[1];
                if (is_subclass_of($className, 'Illuminate\Database\Eloquent\Model')) {
                    $models[] = $className;
                }
            }
        }
    }
    return $models;
}

$modelClasses = getModels(__DIR__.'/app/Models');

echo "Checking " . count($modelClasses) . " models...\n\n";

foreach ($modelClasses as $class) {
    try {
        $model = new $class;
        $table = $model->getTable();
        $connection = $model->getConnectionName() ?: config('database.default');
        
        echo "Model: $class\n";
        echo "Table: $table (Connection: $connection)\n";
        
        if (!Schema::connection($connection)->hasTable($table)) {
            echo "❌ TABLE DOES NOT EXIST IN DATABASE!\n";
            echo "--------------------------------------------------\n";
            continue;
        }
        
        $columns = Schema::connection($connection)->getColumnListing($table);
        $fillable = $model->getFillable();
        
        // 1. Check if fillable columns are missing in the DB
        $missingInDb = array_diff($fillable, $columns);
        if (!empty($missingInDb)) {
            echo "⚠️  Fillable columns missing in DB table:\n";
            foreach ($missingInDb as $col) {
                echo "   - $col\n";
            }
        }
        
        // 2. Check if there are DB columns (excluding standard ones) not in fillable
        $ignoredColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $missingInFillable = array_diff($columns, $fillable, $ignoredColumns);
        if (!empty($missingInFillable)) {
            echo "ℹ️  DB columns not in fillable:\n";
            foreach ($missingInFillable as $col) {
                echo "   - $col\n";
            }
        }
        
        if (empty($missingInDb) && empty($missingInFillable)) {
            echo "✅ Perfectly matched!\n";
        }
        
    } catch (\Throwable $e) {
        echo "💥 Error checking $class: " . $e->getMessage() . "\n";
    }
    echo "--------------------------------------------------\n";
}
