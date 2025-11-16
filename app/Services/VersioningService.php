<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VersioningService
{
    /**
     * Create a new version for the given model
     * 
     * @param Model $model The main model instance
     * @param string $versionTableName The name of the version table
     * @param array $versionableFields Fields that should be versioned
     * @return int The version number that was created
     */
    public function createVersion(Model $model, string $versionTableName, array $versionableFields): int
    {
        // Get the next version number
        $nextVersion = $this->getNextVersionNumber($model, $versionTableName);
        
        // Prepare version data
        $versionData = [
            $this->getForeignKeyName($model) => $model->id,
            'version' => $nextVersion,
            'created_at' => now(),
        ];
        
        // Add versionable fields
        foreach ($versionableFields as $field) {
            $versionData[$field] = $model->{$field};
        }
        
        // Insert into version table
        DB::table($versionTableName)->insert($versionData);
        
        return $nextVersion;
    }
    
    /**
     * Check if the model data has changed compared to the latest version
     * 
     * @param Model $model The main model instance
     * @param string $versionTableName The name of the version table
     * @param array $versionableFields Fields to compare
     * @return bool True if data has changed
     */
    public function hasChanges(Model $model, string $versionTableName, array $versionableFields): bool
    {
        $foreignKey = $this->getForeignKeyName($model);
        
        // Get the latest version
        $latestVersion = DB::table($versionTableName)
            ->where($foreignKey, $model->id)
            ->orderBy('version', 'desc')
            ->first();
            
        if (!$latestVersion) {
            return true; // No versions exist, so this is a change
        }
        
        // Compare each field
        foreach ($versionableFields as $field) {
            if ($model->{$field} !== $latestVersion->{$field}) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the next version number for a model
     * 
     * @param Model $model
     * @param string $versionTableName
     * @return int
     */
    private function getNextVersionNumber(Model $model, string $versionTableName): int
    {
        $foreignKey = $this->getForeignKeyName($model);
        
        $maxVersion = DB::table($versionTableName)
            ->where($foreignKey, $model->id)
            ->max('version');
            
        return ($maxVersion ?? 0) + 1;
    }
    
    /**
     * Get the foreign key name for the model
     * 
     * @param Model $model
     * @return string
     */
    private function getForeignKeyName(Model $model): string
    {
        return strtolower(class_basename($model)) . '_id';
    }
    
    /**
     * Get all versions for a model
     * 
     * @param Model $model
     * @param string $versionTableName
     * @return \Illuminate\Support\Collection
     */
    public function getVersions(Model $model, string $versionTableName): \Illuminate\Support\Collection
    {
        $foreignKey = $this->getForeignKeyName($model);
        
        return DB::table($versionTableName)
            ->where($foreignKey, $model->id)
            ->orderBy('version', 'asc')
            ->get();
    }
}