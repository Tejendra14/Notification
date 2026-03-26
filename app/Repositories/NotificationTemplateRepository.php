<?php

namespace App\Repositories;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Cache;

class NotificationTemplateRepository
{
    private const CACHE_TTL = 3600; // 1 hour

    public function findByName(string $name, ?string $tenantId = null): ?NotificationTemplate
    {
        $cacheKey = $tenantId 
            ? "template:{$tenantId}:{$name}"
            : "template:global:{$name}";
            
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $tenantId) {
            $query = NotificationTemplate::where('name', $name);
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            } else {
                $query->whereNull('tenant_id');
            }
            
            return $query->first();
        });
    }

    public function create(array $data): NotificationTemplate
    {
        $template = NotificationTemplate::create($data);
        $this->clearCache($template);
        return $template;
    }

    public function update(string $id, array $data): ?NotificationTemplate
    {
        $template = NotificationTemplate::find($id);
        
        if ($template) {
            $template->update($data);
            $this->clearCache($template);
        }
        
        return $template;
    }

    private function clearCache(NotificationTemplate $template): void
    {
        Cache::forget("template:{$template->tenant_id}:{$template->name}");
        Cache::forget("template:global:{$template->name}");
    }
}
